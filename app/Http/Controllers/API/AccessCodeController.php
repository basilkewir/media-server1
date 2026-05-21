<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AccessCode\RedeemRequest;
use App\Http\Requests\API\AccessCode\ValidateRequest;
use App\Models\AccessCode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessCodeController extends Controller
{
    use ApiResponse;

    /**
     * Validate an access code without redeeming it.
     */
    public function validateCode(ValidateRequest $request): JsonResponse
    {
        $code = AccessCode::findValid($request->validated('code'));

        if (!$code) {
            return $this->error(
                message: 'Invalid or expired access code.',
                statusCode: 400,
                errorCode: 'INVALID_ACCESS_CODE'
            );
        }

        return $this->success(
            data: [
                'code' => $code->code,
                'type' => $code->type,
                'type_label' => $code->getTypeLabel(),
                'duration_days' => $code->duration_days,
                'expires_at' => $code->expires_at?->toIso8601String(),
                'max_uses' => $code->max_uses,
                'uses_count' => $code->uses_count,
                'remaining_uses' => max(0, $code->max_uses - $code->uses_count),
                'valid' => true,
            ],
            message: 'Access code is valid.'
        );
    }

    /**
     * Redeem an access code.
     */
    public function redeem(RedeemRequest $request): JsonResponse
    {
        $plainCode = $request->validated('code');
        $code = AccessCode::findValid($plainCode);

        if (!$code) {
            return $this->error(
                message: 'Invalid, expired, or fully redeemed access code.',
                statusCode: 400,
                errorCode: 'INVALID_ACCESS_CODE'
            );
        }

        // Check for existing active redemption from this IP
        $existing = $code->redemptions()
            ->where('ip_address', $request->ip())
            ->where('is_active', true)
            ->first();

        if ($existing && $existing->isActive()) {
            return $this->success(
                data: [
                    'redemption_id' => $existing->id,
                    'access_code' => $code->code,
                    'type' => $code->type,
                    'type_label' => $code->getTypeLabel(),
                    'expires_at' => $existing->expires_at?->toIso8601String(),
                    'already_redeemed' => true,
                ],
                message: 'This code was already redeemed from your IP and is still active.'
            );
        }

        // Create redemption
        $redemption = $code->redemptions()->create([
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
            'redeemed_at' => now(),
            'expires_at' => now()->addDays($code->duration_days),
            'is_active' => true,
        ]);

        $code->incrementUsage();

        return $this->success(
            data: [
                'redemption_id' => $redemption->id,
                'access_code' => $code->code,
                'type' => $code->type,
                'type_label' => $code->getTypeLabel(),
                'duration_days' => $code->duration_days,
                'valid_until' => $redemption->expires_at?->toIso8601String(),
                'already_redeemed' => false,
            ],
            message: 'Access code redeemed successfully.',
            statusCode: 201
        );
    }

    /**
     * Check redemption status for the current IP.
     */
    public function status(Request $request): JsonResponse
    {
        $redemptions = \App\Models\CodeRedemption::with('accessCode')
            ->where('ip_address', $request->ip())
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('redeemed_at')
            ->get();

        if ($redemptions->isEmpty()) {
            return $this->success(
                data: ['active' => false, 'subscriptions' => []],
                message: 'No active subscriptions found for this IP.'
            );
        }

        return $this->success(
            data: [
                'active' => true,
                'subscriptions' => $redemptions->map(fn($r) => [
                    'redemption_id' => $r->id,
                    'type' => $r->accessCode->type,
                    'type_label' => $r->accessCode->getTypeLabel(),
                    'redeemed_at' => $r->redeemed_at->toIso8601String(),
                    'expires_at' => $r->expires_at?->toIso8601String(),
                    'days_remaining' => $r->expires_at ? now()->diffInDays($r->expires_at, false) : null,
                ]),
            ],
            message: 'Active subscriptions found.'
        );
    }
}
