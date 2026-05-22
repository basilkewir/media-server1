<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\RelayServer;
use App\Services\RelayBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminRelayServerController extends Controller
{
    public function __construct(protected RelayBroadcastService $relayService) {}

    public function index(): View
    {
        $servers = RelayServer::withCount('broadcasts')->latest()->paginate(20);
        return view('admin.relay-servers.index', compact('servers'));
    }

    public function create(): View
    {
        return view('admin.relay-servers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'name'          => 'required|string|max:255|unique:relay_servers',
            'hostname'      => 'required|string|max:255',
            'port'          => 'required|integer|min:1|max:65535',
            'username'      => 'nullable|string|max:100',
            'password'      => 'nullable|string|max:255',
            'server_type'   => 'required|in:icecast,rtmp,shoutcast',
            'location'      => 'nullable|string|max:100',
            'max_listeners' => 'nullable|integer|min:1',
        ]);

        RelayServer::create($v);
        return redirect()->route('admin.relay-servers.index')->with('success', "Relay server \"{$v['name']}\" added.");
    }

    public function edit(RelayServer $relayServer): View
    {
        return view('admin.relay-servers.edit', compact('relayServer'));
    }

    public function update(Request $request, RelayServer $relayServer): RedirectResponse
    {
        $v = $request->validate([
            'name'          => 'required|string|max:255|unique:relay_servers,name,' . $relayServer->id,
            'hostname'      => 'required|string|max:255',
            'port'          => 'required|integer|min:1|max:65535',
            'username'      => 'nullable|string|max:100',
            'password'      => 'nullable|string|max:255',
            'server_type'   => 'required|in:icecast,rtmp,shoutcast',
            'location'      => 'nullable|string|max:100',
            'max_listeners' => 'nullable|integer|min:1',
        ]);

        $v['is_active'] = $request->boolean('is_active', true);
        $relayServer->update($v);
        return redirect()->route('admin.relay-servers.index')->with('success', 'Relay server updated.');
    }

    public function destroy(RelayServer $relayServer): RedirectResponse
    {
        $relayServer->update(['is_active' => false]);
        return redirect()->route('admin.relay-servers.index')->with('success', 'Relay server deactivated.');
    }

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
