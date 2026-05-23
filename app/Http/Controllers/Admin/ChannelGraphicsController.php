<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChannelGraphicsController extends Controller
{
    public function edit(Channel $channel): View
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        return view('admin.channels.graphics', compact('channel'));
    }

    public function updateLogo(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $request->validate([
            'logo'          => 'nullable|image|mimes:png,jpg,jpeg,gif,svg,webp|max:5120',
            'logo_position' => 'required|in:top-left,top-right,bottom-left,bottom-right',
            'logo_opacity'  => 'required|integer|min:0|max:100',
            'logo_width'    => 'required|integer|min:20|max:800',
            'logo_height'   => 'nullable|integer|min:0|max:800',
            'remove_logo'   => 'boolean',
        ]);

        if ($request->boolean('remove_logo')) {
            if ($channel->logo_path) {
                Storage::disk('public')->delete($channel->logo_path);
            }
            $channel->update(['logo_path' => null]);
        } elseif ($request->hasFile('logo')) {
            if ($channel->logo_path) {
                Storage::disk('public')->delete($channel->logo_path);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $channel->update(['logo_path' => $path]);
        }

        $channel->update($request->only(['logo_position', 'logo_opacity', 'logo_width', 'logo_height']));

        return back()->with('success', 'Logo settings updated.');
    }

    public function updateWatermark(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $request->validate([
            'watermark'          => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'watermark_position' => 'required|in:top-left,top-right,bottom-left,bottom-right',
            'watermark_opacity'  => 'required|integer|min:0|max:100',
            'remove_watermark'   => 'boolean',
        ]);

        if ($request->boolean('remove_watermark')) {
            if ($channel->watermark_path) {
                Storage::disk('public')->delete($channel->watermark_path);
            }
            $channel->update(['watermark_path' => null]);
        } elseif ($request->hasFile('watermark')) {
            if ($channel->watermark_path) {
                Storage::disk('public')->delete($channel->watermark_path);
            }
            $path = $request->file('watermark')->store('watermarks', 'public');
            $channel->update(['watermark_path' => $path]);
        }

        $channel->update($request->only(['watermark_position', 'watermark_opacity']));

        return back()->with('success', 'Watermark settings updated.');
    }

    public function updateTicker(Request $request, Channel $channel): RedirectResponse
    {
        abort_if(!auth()->user()->canManageChannel($channel), 403);

        $validated = $request->validate([
            'ticker_text'      => 'nullable|string|max:500',
            'ticker_position'  => 'required|in:top,bottom',
            'ticker_text_color'=> 'required|string|max:7',
            'ticker_bg_color'  => 'required|string|max:7',
            'ticker_font_size' => 'required|integer|min:12|max:100',
            'ticker_speed_ms'  => 'required|integer|min:50|max:1000',
            'ticker_enabled'   => 'boolean',
        ]);

        $validated['ticker_enabled'] = $request->boolean('ticker_enabled', false);
        $channel->update($validated);

        return back()->with('success', 'Ticker settings updated.');
    }
}
