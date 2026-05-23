<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\RelayServer;
use App\Services\AudioRelayService;
use App\Services\IcecastService;
use App\Services\RelayBroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminIcecastController extends Controller
{
    public function __construct(
        protected IcecastService $icecast,
        protected AudioRelayService $audioRelay,
        protected RelayBroadcastService $relayService,
    ) {}

    public function createStream(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255|unique:channels,name',
            'slug'            => 'required|string|max:255|unique:channels,slug|regex:/^[a-z0-9\-]+$/',
            'description'     => 'nullable|string|max:1000',
            'bitrate_kbps'    => 'nullable|integer|min:16|max:512',
            'vod_playlist_url'=> 'nullable|url|max:2048',
        ]);

        $channel = Channel::create([
            'name'              => $validated['name'],
            'slug'              => $validated['slug'],
            'description'       => $validated['description'] ?? '',
            'bitrate_kbps'      => $validated['bitrate_kbps'] ?? 128,
            'vod_playlist_url'  => $validated['vod_playlist_url'] ?? null,
            'is_active'         => true,
            'is_icecast_enabled'=> false,
        ]);

        $result = $this->icecast->createIcecastStream($channel);

        if (!$result['success']) {
            return back()->with('error', 'Channel created but Icecast setup failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        return back()->with('success', "Radio stream '{$channel->name}' created. Mount: {$result['mount_point']} — Push URL: {$result['source_url']}");
    }

    public function index(): View
    {
        $channels = Channel::where('is_active', true)->orderBy('name')->get()->map(function ($ch) {
            $icecastStats = $ch->is_icecast_enabled ? $this->icecast->getStreamStats($ch) : [];
            $audioInfo    = $this->audioRelay->getAudioRelayInfo($ch);
            $credentials  = $ch->is_icecast_enabled ? $this->icecast->getPushCredentials($ch) : null;

            return [
                'channel'       => $ch,
                'enabled'       => $ch->is_icecast_enabled,
                'mount'         => $this->icecast->getMountPoint($ch),
                'stream_url'    => $ch->is_icecast_enabled ? $this->icecast->getStreamUrl($ch) : null,
                'icecast_stats' => $icecastStats,
                'audio_relay'   => $audioInfo,
                'audio_fallback'=> $ch->audio_fallback_enabled,
                'credentials'   => $credentials,
            ];
        });

        $relayServers = RelayServer::where('is_active', true)->get();
        $icecastHost  = config('services.icecast.host', 'localhost');
        $icecastPort  = config('services.icecast.port', 8000);

        return view('admin.icecast.index', compact('channels', 'relayServers', 'icecastHost', 'icecastPort'));
    }

    public function enable(Channel $channel): RedirectResponse
    {
        $result = $this->icecast->createIcecastStream($channel);

        if (!$result['success']) {
            return back()->with('error', 'Failed to enable Icecast: ' . ($result['error'] ?? 'Unknown error'));
        }

        return back()->with('success', "Icecast enabled. Mount: {$result['mount_point']} — Push URL: {$result['source_url']}");
    }

    public function disable(Channel $channel): RedirectResponse
    {
        $this->audioRelay->stopAudioRelay($channel);
        $this->icecast->disconnectStream($channel);
        $channel->update(['is_icecast_enabled' => false]);
        return back()->with('success', "Icecast disabled for {$channel->name}.");
    }

    /**
     * Start audio relay for a channel.
     */
    public function startAudioRelay(Request $request, Channel $channel): RedirectResponse
    {
        $validated = $request->validate([
            'audio_relay_target_url'  => 'nullable|url',
            'audio_source_url'        => 'nullable|url',
            'audio_relay_playlist_url'=> 'nullable|url',
            'audio_fallback_enabled'  => 'boolean',
            'bitrate_kbps'            => 'nullable|integer|min:16|max:512',
        ]);

        if (!empty($validated['audio_relay_target_url'])) {
            $channel->audio_relay_target_url = $validated['audio_relay_target_url'];
        }
        if (!empty($validated['audio_source_url'])) {
            $channel->audio_source_url = $validated['audio_source_url'];
        }
        if (!empty($validated['audio_relay_playlist_url'])) {
            $channel->audio_relay_playlist_url = $validated['audio_relay_playlist_url'];
        }
        if (isset($validated['bitrate_kbps'])) {
            $channel->bitrate_kbps = (int) $validated['bitrate_kbps'];
        }
        $channel->audio_fallback_enabled = $request->boolean('audio_fallback_enabled', false);
        $channel->audio_relay_enabled = true;
        $channel->save();

        $pid = $this->audioRelay->startAudioRelay($channel);

        if ($pid) {
            return back()->with('success', "Audio relay started (PID: {$pid}).");
        }

        return back()->with('error', 'Could not start audio relay. Check source and target URLs.');
    }

    /**
     * Stop audio relay for a channel.
     */
    public function stopAudioRelay(Channel $channel): RedirectResponse
    {
        $this->audioRelay->stopAudioRelay($channel);
        $channel->update(['audio_relay_enabled' => false]);
        return back()->with('success', "Audio relay stopped for {$channel->name}.");
    }

    /**
     * Forward channel stream to a relay server.
     */
    public function forwardToServer(Request $request, Channel $channel): RedirectResponse
    {
        $validated = $request->validate([
            'relay_server_id' => 'required|exists:relay_servers,id',
            'mode'            => 'required|in:video,audio',
        ]);

        $server = RelayServer::findOrFail($validated['relay_server_id']);

        try {
            if ($validated['mode'] === 'audio') {
                $pid = $this->audioRelay->relayAudioToServer($channel, $server);
                $msg = "Audio forwarding to {$server->name} started.";
            } else {
                $pid = $this->audioRelay->forwardStreamToServer($channel, $server);
                $msg = "Video+audio forwarding to {$server->name} started.";
            }

            if ($pid) {
                return back()->with('success', "{$msg} (PID: {$pid})");
            }
            return back()->with('error', 'No source available. Start the channel stream first.');
        } catch (\Exception $e) {
            return back()->with('error', 'Forward failed: ' . $e->getMessage());
        }
    }

    /**
     * Start relay broadcast (uses existing RelayBroadcastService).
     */
    public function startRelay(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'channel_id'      => 'required|exists:channels,id',
            'relay_server_id' => 'required|exists:relay_servers,id',
        ]);

        try {
            $channel = Channel::findOrFail($v['channel_id']);
            $server  = RelayServer::findOrFail($v['relay_server_id']);
            $this->relayService->startRelay($channel, $server);
            return back()->with('success', "Relay started to {$server->name}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }
}
