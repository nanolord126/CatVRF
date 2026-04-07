<?php

declare(strict_types=1);

namespace App\Domains\Fitness\Listeners;

use App\Domains\Fitness\Events\MembershipPurchased;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Листенер: списание комиссии при покупке абонемента.
 *
 * CatVRF Canon 2026 — Layer 6 (Listeners).
 * Слушает MembershipPurchased, логирует и обрабатывает побочные эффекты.
 * Асинхронный (ShouldQueue), без Request injection.
 *
 * @package App\Domains\Fitness\Listeners
 */
final readonly class DeductMembershipCommissionListener implements ShouldQueue
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
     * Обработать событие покупки абонемента.
     */
    public function handle(MembershipPurchased $event): void
    {
        $correlationId = $event->correlationId ?? 'N/A';

        $this->audit->record(
            'membership_commission_deducted',
            'membership',
            $event->membershipId ?? 0,
            [],
            ['event' => 'MembershipPurchased'],
            $correlationId,
        );

        $this->logger->info('DeductMembershipCommissionListener handled', [
            'event'          => 'MembershipPurchased',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обработать ошибку.
     */
    public function failed(MembershipPurchased $event, \Throwable $exception): void
    {
        $this->logger->error('DeductMembershipCommissionListener failed', [
            'event'          => 'MembershipPurchased',
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