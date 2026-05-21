<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CodeRedemption;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the current client's active subscription from their IP address
 * and attach it to the request for use in controllers and views.
 */
class ResolveClientSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $this->resolveSubscription($request);

        $request->attributes->set('client_subscription', $subscription);

        // Share with all views
        view()->share('clientSubscription', $subscription);

        return $next($request);
    }

    /**
     * Find the best active subscription for this IP.
     * Returns null if no active subscription exists.
     */
    private function resolveSubscription(Request $request): ?array
    {
        $ip = $request->ip();

        $redemption = CodeRedemption::with('accessCode')
            ->where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('redeemed_at')
            ->first();

        if (!$redemption) {
            return null;
        }

        $code = $redemption->accessCode;

        return [
            'redemption_id' => $redemption->id,
            'type' => $code->type,
            'type_label' => $code->getTypeLabel(),
            'redeemed_at' => $redemption->redeemed_at,
            'expires_at' => $redemption->expires_at,
            'days_remaining' => $redemption->expires_at ? now()->diffInDays($redemption->expires_at, false) : null,
        ];
    }
}
