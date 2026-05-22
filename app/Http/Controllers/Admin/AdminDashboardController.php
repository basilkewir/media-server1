<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\OutputTarget;
use App\Models\RelayBroadcast;
use App\Models\StreamEvent;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_channels'  => Channel::count(),
            'live_channels'   => Channel::where('is_live', true)->count(),
            'active_outputs'  => OutputTarget::whereIn('status', ['connected', 'connecting'])->count(),
            'active_relays'   => RelayBroadcast::where('is_active', true)->count(),
        ];

        $channels = Channel::with(['streams' => fn($q) => $q->whereIn('status', ['active', 'fallback'])->latest()->limit(1)])
            ->where('is_active', true)->latest()->limit(12)->get();

        $recentEvents = StreamEvent::with('channel')->latest()->limit(20)->get();

        $driver = config('services.media_server.driver', 'ffmpeg');

        return view('admin.dashboard', compact('stats', 'channels', 'recentEvents', 'driver'));
    }
}
