<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Payment\Events\PaymentRecordUpdated;
use App\Domains\Finances\Services\PaymentDistributionService;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Когда платёж переходит в CAPTURED, происходит зачисление на кошельки
 * (через PaymentDistributionService).
 */
final class CreditWalletOnPaymentCaptured implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(
        private readonly PaymentDistributionService $distributionService,
    ) {}

    public function handle(PaymentRecordUpdated $event): void
    {
        $oldStatus = $event->oldValues['status'] ?? null;
        $newStatus = $event->newValues['status'] ?? null;

        // Обрабатываем только переход в CAPTURED
        if ($newStatus === 'CAPTURED' && $oldStatus !== 'CAPTURED') {
            $payment = $event->paymentRecord;

            $tenant = Tenant::find($payment->tenant_id);

            if (!$tenant) {
                // Платёж без тенанта не распределяется
                return;
            }

            // Сумма в копейках
            $amountKopecks = $payment->amount_kopecks;

            // Распределяем средства (Мерчанту + Маркетплейсу)
            $this->distributionService->distribute(
                amountKopecks: $amountKopecks,
                tenant: $tenant,
                correlationId: $event->correlationId,
                sourceType: get_class($payment),
                sourceId: $payment->id,
            );
        }
    }
}
