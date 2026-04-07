<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\RefundPayment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Payments\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payments\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\Money;
use Modules\Payments\Ports\FraudCheckPort;
use Modules\Payments\Ports\PaymentGatewayPort;

/**
 * UseCase: Создать возврат по платежу.
 */
final class RefundPaymentUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayPort         $gateway,
        private readonly FraudCheckPort             $fraud,
    ) {}

    public function execute(RefundPaymentCommand $cmd): string
    {
        $correlationId = $cmd->correlationId;

        Log::channel('audit')->info('payment.refund.start', [
            'correlation_id' => $correlationId,
            'payment_id'     => $cmd->paymentId,
            'amount'         => $cmd->amountKopeks,
        ]);

        // 1. Fraud check
        $this->fraud->check(
            userId:        $cmd->userId ?? 0,
            operationType: 'payment.refund',
            amount:        $cmd->amountKopeks,
            context:       [
                'tenant_id'      => $cmd->tenantId,
                'correlation_id' => $correlationId,
            ],
        );

        // 2. Найти платёж
        $payment = $this->payments->findById($cmd->paymentId);
        if ($payment === null || $payment->getTenantId() !== $cmd->tenantId) {
            throw PaymentNotFoundException::forId($cmd->paymentId);
        }

        // 3. Вызвать gateway refund (вне транзакции)
        $providerPaymentId = $payment->getProviderPaymentId() ?? '';
        $this->gateway->refund($providerPaymentId, $cmd->amountKopeks);

        // 4. Обновить агрегат + сохранить
        $refundId = Str::uuid()->toString();

        DB::transaction(function () use ($payment, $refundId, $cmd, $correlationId): void {
            $payment->refund(
                $refundId,
                Money::ofKopeks($cmd->amountKopeks),
                $correlationId,
            );
            $this->payments->save($payment);
        });

        // 5. Диспатч
        foreach ($payment->pullDomainEvents() as $event) {
            event($event);
        }

        Log::channel('audit')->info('payment.refund.success', [
            'correlation_id' => $correlationId,
            'refund_id'      => $refundId,
            'payment_id'     => $cmd->paymentId,
        ]);

        return $refundId;
    }
}
