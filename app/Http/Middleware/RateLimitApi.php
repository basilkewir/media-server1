<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply rate limiting to API routes.
 */
class RateLimitApi
{
    public function handle(Request $request, Closure $next, string $limiterName = 'api'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts($limiterName))) {
            $retryAfter = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'meta' => ['retry_after_seconds' => $retryAfter],
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $this->maxAttempts($limiterName),
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Retry-After' => $retryAfter,
            ]);
        }

        RateLimiter::hit($key, $this->decaySeconds($limiterName));

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts($limiterName));
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $this->maxAttempts($limiterName) - RateLimiter::attempts($key)));

        return $response;
    }

    private function resolveRequestSignature(Request $request): string
    {
        $token = $request->attributes->get('api_token_name');
        if ($token) {
            return 'api_token:' . $token;
        }
        return 'ip:' . $request->ip();
    }

    private function maxAttempts(string $limiterName): int
    {
        return match ($limiterName) {
            'api_strict' => 30,
            'api_admin' => 100,
            default => 60,
        };
    }

    private function decaySeconds(string $limiterName): int
    {
        return match ($limiterName) {
            'api_strict' => 60,
            default => 60,
        };
    }
}
