<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\StreamingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminChannelController extends Controller
{
    public function __construct(protected StreamingService $streaming) {}

    public function index(): View
    {
        $channels = Channel::withCount(['streams', 'outputTargets', 'relays'])
            ->latest()
            ->paginate(20);

        return view('admin.channels.index', compact('channels'));
    }

    public function create(): View
    {
        return view('admin.channels.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255|unique:channels',
            'slug'               => 'required|string|max:100|unique:channels|regex:/^[a-z0-9\-]+$/',
            'description'        => 'nullable|string|max:1000',
            'vod_playlist_url'   => 'nullable|string|max:500',
            'rtmp_push_url'      => 'nullable|string|max:500',
            'bitrate_kbps'       => 'nullable|integer|min:100|max:50000',
            'resolution'         => 'nullable|string|max:20',
            'is_active'          => 'boolean',
            'is_icecast_enabled' => 'boolean',
            'is_relay_enabled'   => 'boolean',
        ]);

        $validated['is_active']          = $request->boolean('is_active', true);
        $validated['is_icecast_enabled'] = $request->boolean('is_icecast_enabled');
        $validated['is_relay_enabled']   = $request->boolean('is_relay_enabled');

        Channel::create($validated);

        return redirect()->route('admin.channels.index')
            ->with('success', "Channel \"{$validated['name']}\" created.");
    }

    public function show(Channel $channel): View
    {
        $channel->load(['streams' => fn($q) => $q->latest()->limit(10), 'outputTargets', 'relays.relayServer', 'events' => fn($q) => $q->latest()->limit(20)]);
        $status = $this->streaming->getStreamStatus($channel);

        return view('admin.channels.show', compact('channel', 'status'));
    }

    public function edit(Channel $channel): View
    {
        return view('admin.channels.edit', compact('channel'));
    }

    public function update(Request $request, Channel $channel): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255|unique:channels,name,' . $channel->id,
            'description'        => 'nullable|string|max:1000',
            'vod_playlist_url'   => 'nullable|string|max:500',
            'rtmp_push_url'      => 'nullable|string|max:500',
            'bitrate_kbps'       => 'nullable|integer|min:100|max:50000',
            'resolution'         => 'nullable|string|max:20',
            'is_active'          => 'boolean',
            'is_icecast_enabled' => 'boolean',
            'is_relay_enabled'   => 'boolean',
        ]);

        $validated['is_active']          = $request->boolean('is_active');
        $validated['is_icecast_enabled'] = $request->boolean('is_icecast_enabled');
        $validated['is_relay_enabled']   = $request->boolean('is_relay_enabled');

        $channel->update($validated);

        return redirect()->route('admin.channels.show', $channel)
            ->with('success', 'Channel updated.');
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        $this->streaming->stopStream($channel);
        $channel->delete();

        return redirect()->route('admin.channels.index')
            ->with('success', "Channel \"{$channel->name}\" deleted.");
    }

    public function startStream(Request $request, Channel $channel): RedirectResponse
    {
        $validated = $request->validate(['push_url' => 'required|string']);

        try {
            $this->streaming->startStream($channel, $validated['push_url']);
            return back()->with('success', 'Stream started.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function stopStream(Channel $channel): RedirectResponse
    {
        $this->streaming->stopStream($channel);
        return back()->with('success', 'Stream stopped.');
    }

    public function fallback(Channel $channel): RedirectResponse
    {
        $result = $this->streaming->switchToVODFallback($channel);
        if (!$result) {
            return back()->with('error', 'No VOD playlist configured.');
        }
        return back()->with('success', 'Switched to VOD fallback.');
    }
}
