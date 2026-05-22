<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\StoreRequest;
use App\Http\Requests\Channel\UpdateRequest;
use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function index(): View
    {
        $channels = auth()->user()->manageableChannels()
            ->with(['streams' => fn($q) => $q->latest()->limit(1)])
            ->orderBy('name')
            ->get();

        return view('admin.channels.index', compact('channels'));
    }

    public function create(): View
    {
        abort_if(auth()->user()->isManager(), 403, 'Only admins can create channels.');
        return view('admin.channels.create');
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        abort_if(auth()->user()->isManager(), 403, 'Only admins can create channels.');

        $channel = Channel::create($request->validated());

        return redirect()
            ->route('admin.channels.index')
            ->with('success', "Channel '{$channel->name}' created successfully.");
    }

    public function show(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $channel->load(['streams' => fn($q) => $q->latest()->limit(10)]);

        return view('admin.channels.show', compact('channel'));
    }

    public function edit(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);
        return view('admin.channels.edit', compact('channel'));
    }

    public function update(UpdateRequest $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $channel->update($request->validated());

        return redirect()
            ->route('admin.channels.index')
            ->with('success', "Channel '{$channel->name}' updated successfully.");
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        abort_if(auth()->user()->isManager(), 403, 'Only admins can delete channels.');
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $name = $channel->name;
        $channel->delete();

        return redirect()
            ->route('admin.channels.index')
            ->with('success', "Channel '{$name}' deleted successfully.");
    }
}
