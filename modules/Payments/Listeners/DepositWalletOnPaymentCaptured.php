<?php

declare(strict_types=1);

namespace Modules\Payments\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Payments\Domain\Events\PaymentCaptured;
use Modules\Payments\Ports\WalletPort;

/**
 * Listener: После успешного платежа — автодепозит на кошелёк.
 * Layer 8 — Listeners.
 *
 * ИНТЕГРАЦИЯ Payments → Wallet:
 * PaymentCaptured event → DepositWalletOnPaymentCaptured listener → WalletPort::deposit()
 */
final class DepositWalletOnPaymentCaptured
{
    public function __construct(
        private readonly WalletPort $wallet,
    ) {}

    public function handle(PaymentCaptured $event): void
    {
        $correlationId = $event->correlationId . '.wallet_deposit';

        Log::channel('audit')->info('wallet.auto_deposit.start', [
            'correlation_id' => $correlationId,
            'payment_id'     => $event->paymentId,
            'user_id'        => $event->userId,
            'tenant_id'      => $event->tenantId,
            'amount'         => $event->amount->amount,
        ]);

        try {
            $this->wallet->deposit(
                userId:        $event->userId,
                tenantId:      $event->tenantId,
                amountKopeks:  $event->amount->amount,
                description:   "Пополнение по платежу #{$event->paymentId}",
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('wallet.auto_deposit.success', [
                'correlation_id' => $correlationId,
                'payment_id'     => $event->paymentId,
            ]);
        } catch (\Throwable $e) {
            // Логируем, но НЕ пробрасываем — платёж уже успешен
            Log::channel('audit')->error('wallet.auto_deposit.failed', [
                'correlation_id' => $correlationId,
                'payment_id'     => $event->paymentId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
        }
    }
}
