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
    public function index(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $files = $channel->vodFiles()->orderBy('sort_order')->orderBy('created_at')->get();

        return view('admin.vod.index', compact('channel', 'files'));
    }

    public function store(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $request->validate([
            'file'  => 'required|file|mimes:mp4,mkv,mov,avi,ts,m2ts,flv,webm|max:10240000', // 10 GB
            'title' => 'nullable|string|max:255',
        ]);

        $uploaded = $request->file('file');
        $filename = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();

        Storage::disk('vod')->putFileAs('', $uploaded, $filename);

        // Probe duration with ffprobe if available
        $duration = $this->probeDuration(Storage::disk('vod')->path($filename));

        $channel->vodFiles()->create([
            'title'         => $request->input('title') ?: pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
            'filename'      => $filename,
            'original_name' => $uploaded->getClientOriginalName(),
            'mime_type'     => $uploaded->getMimeType(),
            'size_bytes'    => $uploaded->getSize(),
            'duration_seconds' => $duration,
            'sort_order'    => $channel->vodFiles()->max('sort_order') + 1,
        ]);

        // Auto-generate M3U8 playlist for this channel
        $this->generatePlaylist($channel);

        return back()->with('success', 'Video uploaded successfully.');
    }

    public function destroy(Channel $channel, VodFile $vodFile): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);
        abort_if($vodFile->channel_id !== $channel->id, 403);

        Storage::disk('vod')->delete($vodFile->filename);
        $vodFile->delete();

        $this->generatePlaylist($channel);

        return back()->with('success', 'Video deleted.');
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
     * Generate an M3U8 playlist from all active VOD files for a channel
     * and update the channel's vod_playlist_url to point to it.
     */
    public function generatePlaylist(Channel $channel): void
    {
        $files = $channel->vodFiles()->where('is_active', true)->orderBy('sort_order')->get();

        if ($files->isEmpty()) {
            return;
        }

        $lines = ["#EXTM3U"];
        foreach ($files as $file) {
            $duration = $file->duration_seconds ?? -1;
            $lines[] = "#EXTINF:{$duration},{$file->title}";
            $lines[] = $file->path();
        }

        $playlistName = "vod_{$channel->slug}.m3u8";
        Storage::disk('vod')->put($playlistName, implode("\n", $lines));

        $channel->update([
            'vod_playlist_url' => Storage::disk('vod')->path($playlistName),
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
}
