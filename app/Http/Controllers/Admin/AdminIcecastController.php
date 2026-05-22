<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\IcecastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminIcecastController extends Controller
{
    public function __construct(protected IcecastService $icecast) {}

    public function index(): View
    {
        $channels = Channel::where('is_active', true)->orderBy('name')->get()->map(function ($ch) {
            return [
                'channel'    => $ch,
                'enabled'    => $ch->is_icecast_enabled,
                'mount'      => $this->icecast->getMountPoint($ch),
                'stream_url' => $ch->is_icecast_enabled ? $this->icecast->getStreamUrl($ch) : null,
                'stats'      => $ch->is_icecast_enabled ? $this->icecast->getStreamStats($ch) : [],
            ];
        });

        $icecastHost = config('services.icecast.host', 'localhost');
        $icecastPort = config('services.icecast.port', 8000);

        return view('admin.icecast.index', compact('channels', 'icecastHost', 'icecastPort'));
    }

    public function enable(Channel $channel): RedirectResponse
    {
        $result = $this->icecast->createIcecastStream($channel);

        if (!$result['success']) {
            return back()->with('error', 'Failed to enable Icecast: ' . ($result['error'] ?? 'Unknown error'));
        }

        return back()->with('success', "Icecast enabled for {$channel->name}. Mount: {$result['mount_point']}");
    }

    public function disable(Channel $channel): RedirectResponse
    {
        $this->icecast->disconnectStream($channel);
        $channel->update(['is_icecast_enabled' => false]);
        return back()->with('success', "Icecast disabled for {$channel->name}.");
    }
}
