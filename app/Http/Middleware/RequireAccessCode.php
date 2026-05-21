<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CodeRedemption;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protect stream routes with active access code redemption.
 *
 * Usage: Route::middleware('access_code')...
 */
class RequireAccessCode
{
    public function handle(Request $request, Closure $next, string $requiredType = 'any'): Response
    {
        // Allow bypass with valid API token (admin/system access)
        if ($request->attributes->get('api_token')) {
            return $next($request);
        }

        $ip = $request->ip();

        $query = CodeRedemption::with('accessCode')
            ->where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($requiredType !== 'any') {
            $query->whereHas('accessCode', function ($q) use ($requiredType) {
                $q->where('type', $requiredType);
            });
        }

        $hasActive = $query->exists();

        if (!$hasActive) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active access code required. Please redeem a valid subscription code.',
                    'error_code' => 'ACCESS_CODE_REQUIRED',
                ], 403);
            }

            return response()->view('access-code.required', [
                'redirect_url' => $request->fullUrl(),
            ], 403);
        }

        return $next($request);
    }
}
