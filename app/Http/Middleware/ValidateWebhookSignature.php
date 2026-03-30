<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ValidateWebhookSignature extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly WebhookSignatureValidator $validator,
        ) {
        }

        public function handle(Request $request, Closure $next, string $provider = 'tinkoff'): mixed
        {
            $signature = $request->header('X-Signature') ?? $request->header('Authorization');
            $payload = $request->getContent();

            if (!$signature || !Validator::validate($provider, $payload, $signature)) {
                \Illuminate\Support\Facades\Log::channel('webhook_errors')->warning('Invalid webhook signature', [
                    'provider' => $provider,
                    'path' => $request->path(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            return $next($request);
        }
}
