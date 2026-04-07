<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BeautyTryOnService — инициация AR-примерки для пользователей.
 *
 * Запускает AR-сессию для виртуальной примерки причёсок и макияжа,
 * возвращает session_id для отслеживания результатов.
 */
final readonly class BeautyTryOnService
{
    public function __construct(
        private FraudControlService $fraud,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Инициировать AR-примерку для пользователя.
     *
     * @return array{success: bool, session_id: string, service_type: string, correlation_id: string}
     */
    public function initiateARSession(int $userId, string $serviceType = 'hairstyle', string $correlationId = ''): array
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_ar_session',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $this->logger->info('AR session initiated', [
                'user_id' => $userId,
                'service_type' => $serviceType,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'session_id' => Str::uuid()->toString(),
                'service_type' => $serviceType,
                'correlation_id' => $correlationId,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('AR session failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Получить результаты примерки.
     *
     * @return array{session_id: string, hairstyle_matches: array<mixed>, makeup_matches: array<mixed>, confidence_score: float}
     */
    public function getTryOnResult(string $sessionId, string $correlationId = ''): array
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->logger->info('Try-on result requested', [
            'session_id' => $sessionId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'session_id' => $sessionId,
            'hairstyle_matches' => [],
            'makeup_matches' => [],
            'confidence_score' => 0.0,
        ];
    }
}
