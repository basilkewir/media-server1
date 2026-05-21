<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiToken;

/**
 * Authenticate API requests using Bearer tokens.
 *
 * Supports:
 *   Authorization: Bearer <token>
 *   X-API-Token: <token>
 */
class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. API token required.',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $apiToken = ApiToken::where('token', hash('sha256', $token))
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or expired API token.',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $apiToken->update(['last_used_at' => now()]);
        $request->attributes->set('api_token', $apiToken);
        $request->attributes->set('api_token_name', $apiToken->name);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        $token = $request->header('X-API-Token');
        if ($token) return $token;

        return $request->query('api_token');
    }
}
