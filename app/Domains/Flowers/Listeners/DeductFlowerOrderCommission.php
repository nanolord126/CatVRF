<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;

use App\Domains\Flowers\Events\FlowerOrderCompleted;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Листенер: списание комиссии при завершении заказа цветов.
 *
 * CatVRF Canon 2026 — Layer 6 (Listeners).
 * Слушает FlowerOrderCompleted, начисляет комиссию платформе.
 * Асинхронный (ShouldQueue), без Request injection.
 *
 * @package App\Domains\Flowers\Listeners
 */
final readonly class DeductFlowerOrderCommission implements ShouldQueue
{
    /**
     * @param WalletService   $walletService Сервис кошельков
     * @param AuditService    $audit         Аудит-сервис
     * @param LoggerInterface $logger        Логгер
     */
    public function __construct(
        private WalletService $walletService,
        private AuditService $audit,
        private LoggerInterface $logger,
    ) {}

    /**
     * Обработать событие завершения заказа цветов.
     *
     * Рассчитывает комиссию 14 % и зачисляет на кошелёк платформы.
     */
    public function handle(FlowerOrderCompleted $event): void
    {
        $correlationId    = $event->correlationId ?? 'N/A';
        $grossKopecks     = $event->totalKopecks ?? 0;
        $commissionRate   = 0.14;
        $commissionKopecks = (int) round($grossKopecks * $commissionRate);

        if ($commissionKopecks <= 0) {
            $this->logger->info('DeductFlowerOrderCommission: zero commission, skipped', [
                'order_id'       => $event->orderId ?? 0,
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $this->walletService->credit(
            walletId: $event->platformWalletId ?? 1,
            amount: $commissionKopecks,
            type: BalanceTransactionType::COMMISSION,
            correlationId: $correlationId,
            metadata: [
                'order_id'   => $event->orderId ?? 0,
                'gross'      => $grossKopecks,
                'rate'       => $commissionRate,
                'commission' => $commissionKopecks,
            ],
        );

        $this->audit->record(
            'flower_order_commission_deducted',
            'flower_order',
            $event->orderId ?? 0,
            [],
            [
                'gross'      => $grossKopecks,
                'commission' => $commissionKopecks,
            ],
            $correlationId,
        );

        $this->logger->info('DeductFlowerOrderCommission handled', [
            'order_id'       => $event->orderId ?? 0,
            'commission'     => $commissionKopecks,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обработать ошибку.
     */
    public function failed(FlowerOrderCompleted $event, \Throwable $exception): void
    {
        $this->logger->error('DeductFlowerOrderCommission failed', [
            'event'          => 'FlowerOrderCompleted',
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }
}
