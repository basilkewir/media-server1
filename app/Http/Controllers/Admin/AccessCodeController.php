<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccessCodeRequest;
use App\Models\AccessCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessCodeController extends Controller
{
    public function create(): View
    {
        return view('admin.access-codes.create');
    }

    public function store(StoreAccessCodeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $type = $validated['type'];
        $durationDays = (int) $validated['duration_days'];
        $quantity = (int) $validated['quantity'];
        $maxUses = (int) ($validated['max_uses'] ?? 1);
        $codeLength = (int) ($validated['code_length'] ?? 12);
        $expiresAt = !empty($validated['expires_at']) ? new \DateTime($validated['expires_at']) : null;

        $codes = AccessCode::generateBatch(
            type: $type,
            durationDays: $durationDays,
            quantity: $quantity,
            expiresAt: $expiresAt,
            maxUses: $maxUses,
            codeLength: $codeLength
        );

        // Build summary for the session flash
        $plainCodes = array_map(fn(AccessCode $c) => $c->code, $codes);

        return redirect()
            ->route('admin.access-codes.create')
            ->with('success', "{$quantity} access code(s) generated successfully.")
            ->with('generated_codes', $plainCodes)
            ->with('summary', [
                'type' => $type,
                'type_label' => (new AccessCode(['type' => $type]))->getTypeLabel(),
                'duration_days' => $durationDays,
                'quantity' => $quantity,
                'max_uses' => $maxUses,
                'expires_at' => $expiresAt?->format('Y-m-d'),
            ]);
    }

    public function index(Request $request): View
    {
        $codes = AccessCode::withCount('redemptions')
            ->with('activeRedemptions')
            ->latest()
            ->paginate(25);

        return view('admin.access-codes.index', compact('codes'));
    }

    public function destroy(AccessCode $accessCode): RedirectResponse
    {
        $accessCode->update(['is_active' => false]);

        return redirect()
            ->route('admin.access-codes.index')
            ->with('success', 'Access code deactivated.');
    }
}
