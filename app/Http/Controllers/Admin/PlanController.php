<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderBy('sort_order')->orderBy('price_cents')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => 'required|alpha_dash|unique:plans,slug',
            'description'        => 'nullable|string',
            'tier'               => 'required|in:free,basic,pro,enterprise',
            'storage_quota_mb'   => 'required|integer|min:1|max:1048576',
            'max_channels'       => 'required|integer|min:1|max:1000',
            'max_vod_files'      => 'required|integer|min:1|max:10000',
            'max_upload_mb'      => 'required|integer|min:1|max:1048576',
            'features'           => 'nullable|array',
            'price_cents'        => 'required|integer|min:0',
            'currency'           => 'required|string|size:3',
            'billing_interval'   => 'required|in:month,year,once',
            'is_active'          => 'boolean',
            'sort_order'         => 'nullable|integer|min:0',
        ]);

        $validated['storage_quota_bytes'] = (int) ($validated['storage_quota_mb'] ?? 500) * 1048576;
        $validated['max_upload_bytes']    = (int) ($validated['max_upload_mb'] ?? 500) * 1048576;
        $validated['is_active']           = $request->boolean('is_active', true);

        Plan::create($validated);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', "Plan '{$validated['name']}' created.");
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => 'required|alpha_dash|unique:plans,slug,' . $plan->id,
            'description'        => 'nullable|string',
            'tier'               => 'required|in:free,basic,pro,enterprise',
            'storage_quota_mb'   => 'required|integer|min:1|max:1048576',
            'max_channels'       => 'required|integer|min:1|max:1000',
            'max_vod_files'      => 'required|integer|min:1|max:10000',
            'max_upload_mb'      => 'required|integer|min:1|max:1048576',
            'features'           => 'nullable|array',
            'price_cents'        => 'required|integer|min:0',
            'currency'           => 'required|string|size:3',
            'billing_interval'   => 'required|in:month,year,once',
            'is_active'          => 'boolean',
            'sort_order'         => 'nullable|integer|min:0',
        ]);

        $validated['storage_quota_bytes'] = (int) ($validated['storage_quota_mb'] ?? 500) * 1048576;
        $validated['max_upload_bytes']    = (int) ($validated['max_upload_mb'] ?? 500) * 1048576;
        $validated['is_active']           = $request->boolean('is_active', true);

        $plan->update($validated);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', "Plan '{$validated['name']}' updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->activeSubscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a plan with active subscriptions.');
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('success', "Plan '{$name}' deleted.");
    }
}
