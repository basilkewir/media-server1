<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\VodFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VodController extends Controller
{
    /** 2 GB per channel in bytes */
    private const QUOTA_BYTES = 2 * 1024 * 1024 * 1024;

    /** Max single file upload: 2 GB */
    private const MAX_UPLOAD_MB = 2048;

    public function index(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $files     = $channel->vodFiles()->orderBy('sort_order')->orderBy('created_at')->get();
        $usedBytes = $channel->vodFiles()->where('source_type', 'upload')->sum('size_bytes');

        return view('admin.vod.index', compact('channel', 'files', 'usedBytes'));
    }

    public function store(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        return $this->handleUpload($request, $channel);
    }

    public function storeYoutube(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        return $this->handleYoutube($request, $channel);
    }

    public function destroy(Channel $channel, VodFile $vodFile): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);
        abort_if($vodFile->channel_id !== $channel->id, 403);

        return $this->handleDestroy($channel, $vodFile);
    }

    public function reorder(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->input('order') as $position => $id) {
            $channel->vodFiles()->where('id', $id)->update(['sort_order' => $position]);
        }

        $this->generatePlaylist($channel);

        return back()->with('success', 'Order saved.');
    }

    /**
     * Shared upload handler — used by both admin and VOD manager.
     */
    public function handleUpload(Request $request, Channel $channel): RedirectResponse
    {
        $usedBytes    = $channel->vodFiles()->where('source_type', 'upload')->sum('size_bytes');
        $remainBytes  = max(0, self::QUOTA_BYTES - $usedBytes);
        $remainMb     = (int) floor($remainBytes / 1048576);

        if ($remainBytes <= 0) {
            return back()->withErrors(['file' => 'Channel storage quota of 2 GB is full. Delete some files first.']);
        }

        // Max allowed for this upload = remaining space (capped at 2048 MB for validation)
        $maxMb = min(self::MAX_UPLOAD_MB, max(1, $remainMb));

        $request->validate([
            'file'  => 'required|file|mimes:mp4,mkv,mov,avi,ts,m2ts,flv,webm|max:' . ($maxMb * 1000),
            'title' => 'nullable|string|max:255',
        ]);

        $uploaded = $request->file('file');
        $fileSize = $uploaded->getSize();

        // Double-check actual file size against remaining quota
        if (($usedBytes + $fileSize) > self::QUOTA_BYTES) {
            return back()->withErrors(['file' => 'File exceeds remaining quota (' . $this->formatBytes($remainBytes) . ' left).']);
        }

        $filename = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
        Storage::disk('vod')->putFileAs('', $uploaded, $filename);
        $duration = $this->probeDuration(Storage::disk('vod')->path($filename));

        $channel->vodFiles()->create([
            'source_type'      => 'upload',
            'title'            => $request->input('title') ?: pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
            'filename'         => $filename,
            'original_name'    => $uploaded->getClientOriginalName(),
            'mime_type'        => $uploaded->getMimeType(),
            'size_bytes'       => $fileSize,
            'duration_seconds' => $duration,
            'sort_order'       => $channel->vodFiles()->max('sort_order') + 1,
        ]);

        $this->generatePlaylist($channel);

        return back()->with('success', 'Video uploaded successfully.');
    }

    /**
     * Shared YouTube handler — used by both admin and VOD manager.
     */
    public function handleYoutube(Request $request, Channel $channel): RedirectResponse
    {
        $request->validate([
            'youtube_url' => ['required', 'url', 'regex:/^https?:\/\/(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w\-]{11}/'],
            'title'       => 'nullable|string|max:255',
        ]);

        $url = $request->input('youtube_url');
        preg_match('/(?:v=|youtu\.be\/)(\w[\w\-]{10})/', $url, $m);
        $videoId = $m[1] ?? Str::random(11);
        $title   = $request->input('title') ?: "YouTube: {$videoId}";

        $channel->vodFiles()->create([
            'source_type'   => 'youtube',
            'youtube_url'   => $url,
            'title'         => $title,
            'filename'      => '',
            'original_name' => $url,
            'sort_order'    => $channel->vodFiles()->max('sort_order') + 1,
        ]);

        $this->generatePlaylist($channel);

        return back()->with('success', 'YouTube video added to playlist.');
    }

    /**
     * Shared destroy handler.
     */
    public function handleDestroy(Channel $channel, VodFile $vodFile): RedirectResponse
    {
        abort_if($vodFile->channel_id !== $channel->id, 403);

        if (!$vodFile->isYoutube() && $vodFile->filename) {
            Storage::disk('vod')->delete($vodFile->filename);
        }

        $vodFile->delete();
        $this->generatePlaylist($channel);

        return back()->with('success', 'Video removed.');
    }

    /**
     * Generate an M3U8 playlist from all active VOD files.
     * Uploaded files use their HTTP URL; YouTube entries use yt-dlp pipe syntax
     * so FFmpeg can pull them during fallback ingest.
     */
    public function generatePlaylist(Channel $channel): void
    {
        $files = $channel->vodFiles()->where('is_active', true)->orderBy('sort_order')->get();

        if ($files->isEmpty()) {
            return;
        }

        $lines = ['#EXTM3U'];

        foreach ($files as $file) {
            $duration = $file->duration_seconds ?? -1;
            $lines[]  = "#EXTINF:{$duration},{$file->title}";

            if ($file->isYoutube()) {
                // FFmpeg reads YouTube via yt-dlp: pipe:// syntax
                // Format: ytdl://URL  — supported by ffmpeg when built with yt-dlp
                $lines[] = "ytdl://{$file->youtube_url}";
            } else {
                $lines[] = $file->ffmpegUrl();
            }
        }

        $playlistName = "vod_{$channel->slug}.m3u8";
        Storage::disk('vod')->put($playlistName, implode("\n", $lines));

        $channel->update([
            'vod_playlist_url' => Storage::disk('vod')->url($playlistName),
        ]);
    }

    private function probeDuration(string $path): ?int
    {
        try {
            $ffprobe = config('services.ffmpeg.probe_path', '/usr/bin/ffprobe');
            if (!file_exists($ffprobe)) return null;

            $output = shell_exec(
                escapeshellcmd($ffprobe) .
                ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' .
                escapeshellarg($path) . ' 2>/dev/null'
            );

            return $output ? (int) round((float) trim($output)) : null;
        } catch (\Exception) {
            return null;
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1024, 2) . ' KB';
    }
}
