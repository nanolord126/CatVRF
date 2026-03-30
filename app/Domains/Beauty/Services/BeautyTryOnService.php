<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyTryOnService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}
        /**
         * Инициировать AR-примерку для пользователя.
         */
        public function initiateARSession(int $userId, string $serviceType = 'hairstyle', string $correlationId = ''): array
        {
            $correlationId = $correlationId ?: Str::uuid()->toString();

            $this->fraudControlService->check(
                $userId,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId
            );

            try {
                Log::channel('audit')->info('AR session initiated', [
                    'user_id' => $userId,
                    'service_type' => $serviceType,
                    'correlation_id' => $correlationId,
                ]);
                // Возвращает session_id для отслеживания

                return [
                    'success' => true,
                    'session_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'service_type' => $serviceType,
                    'correlation_id' => $correlationId,
                ];
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AR session failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Получить результаты примерки (примерные координаты).
         */
        public function getTryOnResult(string $sessionId, string $correlationId = ''): array
        {
            $correlationId = $correlationId ?: Str::uuid()->toString();

            Log::channel('audit')->info('Try-on result requested', [
                'session_id' => $sessionId,
                'correlation_id' => $correlationId,
            ]);

            return [
                'session_id' => $sessionId,
                'hairstyle_matches' => [], // Array of recommended hairstyles
                'makeup_matches' => [], // Array of recommended makeup products
                'confidence_score' => 0.0,
            ];
        }
}
