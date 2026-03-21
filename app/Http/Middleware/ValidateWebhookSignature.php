<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Webhook\WebhookSignatureValidator;
use Closure;
use Illuminate\Http\Request;

final class ValidateWebhookSignature
{
    public function __construct(
        private readonly WebhookSignatureValidator $validator,
    ) {
    }

    public function handle(Request $request, Closure $next, string $provider = 'tinkoff'): mixed
    {
        $signature = $request->header('X-Signature') ?? $request->header('Authorization');
        $payload = $request->getContent();

        if (!$signature || !$this->validator->validate($provider, $payload, $signature)) {
            \Illuminate\Support\Facades\Log::channel('webhook_errors')->warning('Invalid webhook signature', [
                'provider' => $provider,
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
