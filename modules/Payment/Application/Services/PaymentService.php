<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Payment\Domain\Entities\Payment;
use Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Events\PaymentSucceeded;
use Modules\Payment\Domain\Events\PaymentFailed;
use Modules\Payment\Domain\Events\PaymentPartiallyPaid;
use Illuminate\Contracts\Events\Dispatcher;
use Modules\Loyalty\Application\Services\LoyaltyService;
use App\Domain\Audit\Events\AuditEvent;
use Ramsey\Uuid\UuidInterface;
use Psr\Log\LoggerInterface;

final readonly class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private readonly LoggerInterface $logger,
        private readonly Dispatcher $eventDispatcher,
        private readonly UuidInterface $uuid,
        private ?LoyaltyService $loyaltyService = null,
    ) {}

    public function handleSuccess(Payment $payment, ?string $gatewayTransactionId = null, ?string $gatewayResponse = null): void
    {
        $correlationId = $this->uuid->toString();

        $oldStatus = $payment->status;
        $succeededPayment = $payment->markSucceeded($gatewayTransactionId, $gatewayResponse);
        $this->paymentRepository->save($succeededPayment);

        $this->logger->info('Payment succeeded', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'payable_type' => $payment->payableType,
            'payable_id' => $payment->payableId,
            'correlation_id' => $correlationId,
        ]);

        // Dispatch Domain Event for audit (Clean Architecture)
        $this->eventDispatcher->dispatch(AuditEvent::action(
            action: 'payment_capture_success',
            subjectType: 'Payment',
            subjectId: $payment->id,
            context: [
                'payment_type' => 'capture',
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'RUB',
                'status' => 'success',
                'payable_type' => $payment->payableType,
                'payable_id' => $payment->payableId,
                'gateway_transaction_id' => $gatewayTransactionId,
                'old_status' => $oldStatus,
                'new_status' => $succeededPayment->status,
                'gateway_response' => $gatewayResponse ? substr($gatewayResponse, 0, 500) : null,
            ],
            correlationId: $correlationId
        ));

        $this->eventDispatcher->dispatch(new PaymentSucceeded($succeededPayment));

        // Process loyalty points if service is available
        if ($this->loyaltyService !== null && $payment->payableId !== null) {
            $this->loyaltyService->processPayment($payment->payableType, $payment->payableId, $payment->amount);
        }
    }

    public function handleFailure(Payment $payment, ?string $errorMessage = null): void
    {
        $correlationId = $this->uuid->toString();

        $oldStatus = $payment->status;
        $failedPayment = $payment->markFailed($errorMessage);
        $this->paymentRepository->save($failedPayment);

        $this->logger->warning('Payment failed', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'error' => $errorMessage,
            'correlation_id' => $correlationId,
        ]);

        // Dispatch Domain Event for audit (Clean Architecture)
        $this->eventDispatcher->dispatch(AuditEvent::action(
            action: 'payment_failed',
            subjectType: 'Payment',
            subjectId: $payment->id,
            context: [
                'payment_type' => 'payment',
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'RUB',
                'status' => 'failed',
                'payable_type' => $payment->payableType,
                'payable_id' => $payment->payableId,
                'error_message' => $errorMessage,
                'old_status' => $oldStatus,
                'new_status' => $failedPayment->status,
            ],
            correlationId: $correlationId
        ));

        $this->eventDispatcher->dispatch(new PaymentFailed($failedPayment, $errorMessage));
    }

    public function handlePartialPayment(Payment $payment): void
    {
        $correlationId = $this->uuid->toString();

        $oldStatus = $payment->status;
        $partialPayment = $payment->markPartiallyPaid();
        $this->paymentRepository->save($partialPayment);

        $this->logger->info('Payment partially completed', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'correlation_id' => $correlationId,
        ]);

        // Dispatch Domain Event for audit (Clean Architecture)
        $this->eventDispatcher->dispatch(AuditEvent::action(
            action: 'payment_partial',
            subjectType: 'Payment',
            subjectId: $payment->id,
            context: [
                'payment_type' => 'partial',
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'RUB',
                'status' => 'partial',
                'payable_type' => $payment->payableType,
                'payable_id' => $payment->payableId,
                'old_status' => $oldStatus,
                'new_status' => $partialPayment->status,
            ],
            correlationId: $correlationId
        ));

        $this->eventDispatcher->dispatch(new PaymentPartiallyPaid($partialPayment));
    }

    public function handleRefund(Payment $payment, string $reason): void
    {
        $correlationId = $this->uuid->toString();

        $oldStatus = $payment->status;
        $refundedPayment = $payment->markRefunded($reason);
        $this->paymentRepository->save($refundedPayment);

        $this->logger->info('Payment refunded', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        // Dispatch Domain Event for audit (Clean Architecture)
        $this->eventDispatcher->dispatch(AuditEvent::action(
            action: 'payment_refunded',
            subjectType: 'Payment',
            subjectId: $payment->id,
            context: [
                'payment_type' => 'refund',
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'RUB',
                'status' => 'refunded',
                'payable_type' => $payment->payableType,
                'payable_id' => $payment->payableId,
                'refund_reason' => $reason,
                'old_status' => $oldStatus,
                'new_status' => $refundedPayment->status,
            ],
            correlationId: $correlationId
        ));

        // Refund loyalty points if service is available
        if ($this->loyaltyService !== null && $payment->payableId !== null) {
            $this->loyaltyService->refundPayment($payment->payableType, $payment->payableId, $payment->amount);
        }
    }
}
