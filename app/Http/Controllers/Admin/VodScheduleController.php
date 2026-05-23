<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\VodFile;
use App\Models\VodSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VodScheduleController extends Controller
{
    public function index(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $schedules = $channel->vodSchedules()
            ->with('vodFile')
            ->orderBy('play_at')
            ->get();

        $vodFiles = $channel->vodFiles()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.vod-schedules.index', compact('channel', 'schedules', 'vodFiles'));
    }

    public function store(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $validated = $request->validate([
            'vod_file_id'             => 'required|exists:vod_files,id',
            'title'                   => 'nullable|string|max:255',
            'play_at'                 => 'required|date',
            'end_at'                  => 'nullable|date|after:play_at',
            'is_repeating'            => 'boolean',
            'repeat_days'             => 'nullable|array',
            'repeat_days.*'           => 'integer|min:1|max:7',
            'override_default_playlist' => 'boolean',
        ]);

        $validated['channel_id'] = $channel->id;
        $validated['is_repeating'] = $request->boolean('is_repeating', false);
        $validated['override_default_playlist'] = $request->boolean('override_default_playlist', false);

        VodSchedule::create($validated);

        return back()->with('success', 'Schedule entry created.');
    }

    public function update(Request $request, Channel $channel, VodSchedule $vodSchedule): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);
        abort_if($vodSchedule->channel_id !== $channel->id, 403);

        $validated = $request->validate([
            'vod_file_id'             => 'required|exists:vod_files,id',
            'title'                   => 'nullable|string|max:255',
            'play_at'                 => 'required|date',
            'end_at'                  => 'nullable|date|after:play_at',
            'is_repeating'            => 'boolean',
            'repeat_days'             => 'nullable|array',
            'repeat_days.*'           => 'integer|min:1|max:7',
            'override_default_playlist' => 'boolean',
        ]);

        $validated['is_repeating'] = $request->boolean('is_repeating', false);
        $validated['override_default_playlist'] = $request->boolean('override_default_playlist', false);

        $vodSchedule->update($validated);

        return back()->with('success', 'Schedule updated.');
    }

    public function destroy(Channel $channel, VodSchedule $vodSchedule): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);
        abort_if($vodSchedule->channel_id !== $channel->id, 403);

        $vodSchedule->delete();

        return back()->with('success', 'Schedule entry removed.');
    }

    public function toggle(Channel $channel, VodSchedule $vodSchedule): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);
        abort_if($vodSchedule->channel_id !== $channel->id, 403);

        $vodSchedule->update(['is_active' => !$vodSchedule->is_active]);

        return back()->with('success', 'Schedule ' . ($vodSchedule->is_active ? 'enabled' : 'disabled') . '.');
    }
}
