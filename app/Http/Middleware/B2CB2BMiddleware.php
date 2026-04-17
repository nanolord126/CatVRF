<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class B2CB2BMiddleware
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}



    public function handle(Request $request, Closure $next): mixed
        {
            $correlationId = $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            try {
                // Определяем режим по наличию INN и business_card_id
                $inn = $request->input('inn') ?? $request->header('X-Inn');
                $businessCardId = $request->input('business_card_id') ?? $request->header('X-Business-Card-Id');

                // B2B если есть оба параметра
                $isB2B = !empty($inn) && !empty($businessCardId);
                $isB2C = !$isB2B;

                // Устанавливаем флаги в request
                $request->merge([
                    'b2c_mode' => $isB2C,
                    'b2b_mode' => $isB2B,
                    'mode_type' => $isB2B ? 'b2b' : 'b2c',
                ]);

                // Логируем определение режима
                $this->logger->channel('audit')->debug('B2C/B2B mode determined', [
                    'user_id' => $this->guard->id(),
                    'mode' => $request->get('mode_type'),
                    'inn' => $inn ? '***' . substr($inn, -4) : null,
                    'path' => $request->path(),
                    'correlation_id' => $correlationId,
                ]);

                // Если B2B, проверяем, что юзер имеет доступ к этому бизнес-аккаунту
                if ($isB2B && $this->guard->check()) {
                    $user = $this->guard->user();
                    $hasBusinessAccess = $user->businesses()
                        ->where('inn', $inn)
                        ->where('business_card_id', $businessCardId)
                        ->exists();

                    if (!$hasBusinessAccess) {
                        $this->logger->channel('fraud_alert')->warning('Unauthorized B2B access attempt', [
                            'user_id' => $user->id,
                            'inn' => '***' . substr($inn, -4),
                            'path' => $request->path(),
                            'correlation_id' => $correlationId,
                        ]);

                        return $this->response->json([
                            'error' => 'Unauthorized B2B access',
                            'correlation_id' => $correlationId,
                        ], 403);
                    }
                }

                return $next($request);

            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('B2C/B2B middleware error', [
                    'error' => $e->getMessage(),
                    'path' => $request->path(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->response->json([
                    'error' => 'Internal server error',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
