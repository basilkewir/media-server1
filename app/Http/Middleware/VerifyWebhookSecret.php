<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.webhook.secret');

        if (empty($secret)) {
            return $next($request);
        }

        $provided = $request->header('X-Webhook-Secret')
            ?? $request->input('webhook_secret')
            ?? '';

        if (!hash_equals($secret, $provided)) {
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
