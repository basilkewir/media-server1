<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\StreamEvent;
use App\Services\StreamingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StreamAdminController extends Controller
{
    public function __construct(
        protected StreamingService $streamingService
    ) {}

    public function start(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $validated = $request->validate([
            'push_url' => 'required|string|stream_url|max:2048',
        ]);

        try {
            $this->streamingService->startStream($channel, $validated['push_url']);
            return back()->with('success', 'Stream started successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to start stream: ' . $e->getMessage());
        }
    }

    public function stop(Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        try {
            $this->streamingService->stopStream($channel);
            return back()->with('success', 'Stream stopped successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to stop stream: ' . $e->getMessage());
        }
    }

    public function fallback(Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        try {
            $stream = $this->streamingService->switchToVODFallback($channel);

            if (!$stream) {
                return back()->with('error', 'No VOD playlist configured for this channel.');
            }

            return back()->with('success', 'Switched to VOD fallback successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to switch to VOD fallback: ' . $e->getMessage());
        }
    }

    public function recover(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $validated = $request->validate([
            'push_url' => 'required|string|stream_url|max:2048',
        ]);

        try {
            $this->streamingService->recoverFromFallback($channel, $validated['push_url']);
            return back()->with('success', 'Recovered to live stream successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to recover from fallback: ' . $e->getMessage());
        }
    }

    public function events(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $events = StreamEvent::where('channel_id', $channel->id)
            ->latest()
            ->paginate(50);

        return view('admin.channels.events', compact('channel', 'events'));
    }
}
