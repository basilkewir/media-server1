<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\OutputTarget;
use App\Services\OutputManager;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminOutputController extends Controller
{
    public function __construct(protected OutputManager $outputManager) {}

    public function index(): View
    {
        $targets = OutputTarget::with('channel')->orderBy('channel_id')->orderBy('name')->paginate(30);
        return view('admin.outputs.index', compact('targets'));
    }

    public function create(Request $request): View
    {
        $channels = Channel::where('is_active', true)->orderBy('name')->get();
        $selected = $request->query('channel_id');
        return view('admin.outputs.create', compact('channels', 'selected'));
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'channel_id'         => 'required|exists:channels,id',
            'name'               => 'required|string|max:255',
            'output_url'         => 'required|string|max:500',
            'output_protocol'    => 'required|in:rtmp,rtmps,srt,hls_push,mpeg_ts_udp,mpeg_ts_tcp,rtp,icecast,shoutcast,file',
            'trigger'            => 'required|in:always,live_only,fallback_only,manual',
            'video_codec'        => 'nullable|in:copy,libx264,libx265',
            'audio_codec'        => 'nullable|in:copy,aac,libmp3lame',
            'video_bitrate_kbps' => 'nullable|integer|min:100|max:50000',
            'audio_bitrate_kbps' => 'nullable|integer|min:32|max:512',
            'resolution'         => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
            'framerate'          => 'nullable|integer|min:1|max:120',
            'srt_latency_ms'     => 'nullable|integer|min:20|max:8000',
            'srt_passphrase'     => 'nullable|string|min:10|max:79',
        ]);

        $v['is_enabled'] = $request->boolean('is_enabled', true);
        $target = OutputTarget::create($v);

        return redirect()->route('admin.channels.show', $v['channel_id'])
            ->with('success', "Output \"{$target->name}\" created.");
    }

    public function edit(OutputTarget $output): View
    {
        $channels = Channel::where('is_active', true)->orderBy('name')->get();
        return view('admin.outputs.edit', compact('output', 'channels'));
    }

    public function update(Request $request, OutputTarget $output): RedirectResponse
    {
        $v = $request->validate([
            'name'               => 'required|string|max:255',
            'output_url'         => 'required|string|max:500',
            'output_protocol'    => 'required|in:rtmp,rtmps,srt,hls_push,mpeg_ts_udp,mpeg_ts_tcp,rtp,icecast,shoutcast,file',
            'trigger'            => 'required|in:always,live_only,fallback_only,manual',
            'video_codec'        => 'nullable|in:copy,libx264,libx265',
            'audio_codec'        => 'nullable|in:copy,aac,libmp3lame',
            'video_bitrate_kbps' => 'nullable|integer|min:100|max:50000',
            'audio_bitrate_kbps' => 'nullable|integer|min:32|max:512',
            'resolution'         => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
            'framerate'          => 'nullable|integer|min:1|max:120',
            'srt_latency_ms'     => 'nullable|integer|min:20|max:8000',
            'srt_passphrase'     => 'nullable|string|min:10|max:79',
        ]);

        $v['is_enabled'] = $request->boolean('is_enabled');

        if ($output->isRunning() && (isset($v['output_url']) || isset($v['output_protocol']))) {
            $this->outputManager->stopTarget($output);
        }

        $output->update($v);

        return redirect()->route('admin.channels.show', $output->channel_id)
            ->with('success', 'Output updated.');
    }

    public function destroy(OutputTarget $output): RedirectResponse
    {
        $channelId = $output->channel_id;
        $this->outputManager->stopTarget($output);
        $output->delete();
        return redirect()->route('admin.channels.show', $channelId)->with('success', 'Output deleted.');
    }

    public function start(OutputTarget $output): RedirectResponse
    {
        $this->outputManager->startTarget($output);
        return back()->with('success', "Output \"{$output->name}\" started.");
    }

    public function stop(OutputTarget $output): RedirectResponse
    {
        $this->outputManager->stopTarget($output);
        return back()->with('success', "Output \"{$output->name}\" stopped.");
    }

    public function restart(OutputTarget $output): RedirectResponse
    {
        $this->outputManager->stopTarget($output);
        $output->update(['reconnect_attempts' => 0]);
        $this->outputManager->startTarget($output);
        return back()->with('success', "Output \"{$output->name}\" restarted.");
    }
}
