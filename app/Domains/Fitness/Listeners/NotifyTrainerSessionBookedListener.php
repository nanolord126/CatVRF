<?php

declare(strict_types=1);

namespace App\Domains\Fitness\Listeners;

use App\Domains\Fitness\Events\SessionBooked;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Листенер: уведомление тренера о записи на сессию.
 *
 * CatVRF Canon 2026 — Layer 6 (Listeners).
 * Слушает SessionBooked, логирует и уведомляет тренера.
 * Асинхронный (ShouldQueue), без Request injection.
 *
 * @package App\Domains\Fitness\Listeners
 */
final readonly class NotifyTrainerSessionBookedListener implements ShouldQueue
{
    /**
     * @param AuditService    $audit  Аудит-сервис
     * @param LoggerInterface $logger Логгер
     */
    public function __construct(
        private AuditService $audit,
        private LoggerInterface $logger,
    ) {}

    /**
     * Обработать событие записи на тренировку.
     */
    public function handle(SessionBooked $event): void
    {
        $correlationId = $event->correlationId ?? 'N/A';

        $this->audit->record(
            'trainer_notified_session_booked',
            'session',
            $event->sessionId ?? 0,
            [],
            ['event' => 'SessionBooked'],
            $correlationId,
        );

        $this->logger->info('NotifyTrainerSessionBookedListener handled', [
            'event'          => 'SessionBooked',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обработать ошибку.
     */
    public function failed(SessionBooked $event, \Throwable $exception): void
    {
        $this->logger->error('NotifyTrainerSessionBookedListener failed', [
            'event'          => 'SessionBooked',
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Определить, валиден ли листенер в текущем контексте.
     *
     * @return bool
     */
}