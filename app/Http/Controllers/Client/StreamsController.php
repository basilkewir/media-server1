<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\View\View;

class StreamsController extends Controller
{
    public function index(): View
    {
        $channels = Channel::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('client.streams', compact('channels'));
    }

    public function show(Channel $channel): View
    {
        return view('client.stream', compact('channel'));
    }
}
