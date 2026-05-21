<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $subscription = $request->attributes->get('client_subscription');

        $stats = [
            'subscription_type' => $subscription['type_label'] ?? 'Guest',
            'days_remaining' => $subscription['days_remaining'] ?? 0,
            'expires_at' => $subscription['expires_at'] ?? null,
        ];

        $channels = Channel::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('client.dashboard', compact('stats', 'channels'));
    }
}
