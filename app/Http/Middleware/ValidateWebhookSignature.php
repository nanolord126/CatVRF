declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Webhook\WebhookSignatureValidator;
use Closure;
use Illuminate\Http\Request;

final /**
 * ValidateWebhookSignature
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ValidateWebhookSignature
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
            \Illuminate\Support\Facades\$this->log->channel('webhook_errors')->warning('Invalid webhook signature', [
                'provider' => $provider,
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
