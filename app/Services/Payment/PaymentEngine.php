<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Services\FraudControlService;
use App\Domains\Wallet\Services\AtomicWalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * PaymentEngine - Orchestrator for payment operations.
 *
 * Coordinates payment flow: idempotency check → fraud check → wallet hold → gateway call → capture.
 * Gateway calls are OUTSIDE DB transactions to prevent connection holding.
 *
 * @package App\Services\Payment
 */
final readonly class PaymentEngine
{
    public function __construct(
        private IdempotencyService $idempotency,
        private PaymentGatewayService $gateway,
        private AtomicWalletService $wallet,
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Initialize payment with full orchestration.
     *
     * Flow:
     * 1. Check idempotency (Redis)
     * 2. Rule-based fraud check (fast)
     * 3. DB transaction: create payment record + hold wallet
     * 4. Gateway call (OUTSIDE transaction)
     * 5. Update payment record with gateway response
     *
     * @param int $amount Amount in kopecks
     * @param int $tenantId
     * @param int $userId
     * @param string $provider Gateway provider (tinkoff, yookassa, sber)
     * @param string $paymentMethod Payment method (card, sbp)
     * @param bool $hold Whether to hold amount in wallet
     * @param string|null $idempotencyKey
     * @param string|null $correlationId
     * @param array $metadata
     * @return PaymentTransaction
     */
    public function initPayment(
        int $amount,
        int $tenantId,
        int $userId,
        string $provider = 'tinkoff',
        string $paymentMethod = 'card',
        bool $hold = true,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        array $metadata = [],
    ): PaymentTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $idempotencyKey = $idempotencyKey ?? Str::uuid()->toString();

        $this->logger->info('PaymentEngine: initPayment started', [
            'correlation_id' => $correlationId,
            'idempotency_key' => $idempotencyKey,
            'amount' => $amount,
            'provider' => $provider,
        ]);

        // 1. Check idempotency
        $idempotencyPayload = [
            'amount' => $amount,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'provider' => $provider,
            'payment_method' => $paymentMethod,
            'hold' => $hold,
        ];

        $existingResponse = $this->idempotency->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: $idempotencyPayload,
        );

        if ($existingResponse !== null) {
            $this->logger->info('PaymentEngine: idempotency hit', [
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
            ]);

            return PaymentTransaction::where('uuid', $existingResponse['payment_uuid'])->firstOrFail();
        }

        // 2. Rule-based fraud check (fast path, no ML)
        $fraudResult = $this->fraud->check(
            userId: $userId,
            operationType: 'payment_init',
            amount: $amount,
            ipAddress: request()->ip() ?? '127.0.0.1',
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->logger->warning('PaymentEngine: blocked by fraud check', [
                'correlation_id' => $correlationId,
                'fraud_score' => $fraudResult['score'],
            ]);

            throw new \RuntimeException('Payment blocked by fraud detection');
        }

        // 3. DB transaction: create payment record + hold wallet
        $payment = $this->db->transaction(function () use (
            $amount,
            $tenantId,
            $userId,
            $provider,
            $paymentMethod,
            $hold,
            $idempotencyKey,
            $correlationId,
            $metadata,
            $fraudResult
        ) {
            $wallet = Wallet::where('tenant_id', $tenantId)->firstOrFail();

            $payment = PaymentTransaction::create([
                'uuid' => Str::uuid()->toString(),
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'idempotency_key' => $idempotencyKey,
                'provider' => $provider,
                'provider_code' => $provider,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'currency' => 'RUB',
                'hold' => $hold,
                'hold_amount' => $hold ? $amount : 0,
                'correlation_id' => $correlationId,
                'fraud_score' => $fraudResult['score'],
                'metadata' => $metadata,
                'tags' => ['payment_init' => true],
            ]);

            // Hold wallet amount if required
            if ($hold) {
                $this->wallet->hold(
                    walletId: $wallet->id,
                    amount: $amount,
                    correlationId: $correlationId,
                    sourceType: 'payment',
                    sourceId: $payment->id,
                );

                $payment->status = 'authorized';
                $payment->authorized_at = now();
                $payment->save();
            }

            return $payment;
        });

        // 4. Gateway call (OUTSIDE transaction - CRITICAL)
        try {
            $gatewayResponse = $this->gateway->initiatePayment(
                [
                    'amount' => $amount,
                    'currency' => 'RUB',
                    'payment_method' => $paymentMethod,
                ],
                $correlationId,
            );

            // 5. Update payment with gateway response
            $payment->update([
                'provider_payment_id' => $gatewayResponse['provider_payment_id'] ?? null,
                'payment_url' => $gatewayResponse['payment_url'] ?? null,
                'confirmation_url' => $gatewayResponse['confirmation_url'] ?? null,
            ]);

            // Store idempotency response
            $this->idempotency->storeResponse(
                operation: 'payment_init',
                idempotencyKey: $idempotencyKey,
                response: ['payment_uuid' => $payment->uuid],
            );

            $this->logger->info('PaymentEngine: initPayment successful', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'provider_payment_id' => $payment->provider_payment_id,
            ]);

            return $payment;
        } catch (\Throwable $e) {
            // Rollback wallet hold on gateway failure
            if ($hold && $payment->status === 'authorized') {
                try {
                    $this->wallet->credit(
                        walletId: $payment->wallet_id,
                        amount: $amount,
                        type: \App\Domains\Wallet\Enums\BalanceTransactionType::RELEASE_HOLD,
                        correlationId: $correlationId,
                        sourceType: 'payment',
                        sourceId: $payment->id,
                    );

                    $payment->status = 'failed';
                    $payment->save();
                } catch (\Throwable $rollbackError) {
                    $this->logger->error('PaymentEngine: wallet rollback failed', [
                        'correlation_id' => $correlationId,
                        'error' => $rollbackError->getMessage(),
                    ]);
                }
            }

            $this->logger->error('PaymentEngine: gateway call failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Capture payment after authorization.
     *
     * Flow:
     * 1. Validate payment status
     * 2. Gateway call (OUTSIDE transaction)
     * 3. DB transaction: debit wallet + update payment status
     *
     * @param string $paymentUuid
     * @param int|null $amount
     * @param string|null $correlationId
     * @return PaymentTransaction
     */
    public function capture(
        string $paymentUuid,
        ?int $amount = null,
        ?string $correlationId = null,
    ): PaymentTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $payment = PaymentTransaction::where('uuid', $paymentUuid)->firstOrFail();

        if ($payment->status !== 'authorized') {
            throw new \RuntimeException("Payment must be authorized to capture, current: {$payment->status}");
        }

        $captureAmount = $amount ?? $payment->hold_amount;

        if ($captureAmount > $payment->hold_amount) {
            throw new \RuntimeException("Capture amount exceeds hold amount");
        }

        $this->logger->info('PaymentEngine: capture started', [
            'correlation_id' => $correlationId,
            'payment_uuid' => $paymentUuid,
            'amount' => $captureAmount,
        ]);

        // 1. Gateway call (OUTSIDE transaction - CRITICAL)
        try {
            $gatewayResponse = $this->gateway->capture($payment, $correlationId);
        } catch (\Throwable $e) {
            $this->logger->error('PaymentEngine: gateway capture failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // 2. DB transaction: debit wallet + update payment
        return $this->db->transaction(function () use ($payment, $captureAmount, $correlationId) {
            // Release hold and debit actual amount
            $this->wallet->credit(
                walletId: $payment->wallet_id,
                amount: $payment->hold_amount,
                type: \App\Domains\Wallet\Enums\BalanceTransactionType::RELEASE_HOLD,
                correlationId: $correlationId,
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $this->wallet->debit(
                walletId: $payment->wallet_id,
                amount: $captureAmount,
                type: \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL,
                correlationId: $correlationId,
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $payment->update([
                'status' => 'captured',
                'captured_at' => now(),
                'captured_amount' => $captureAmount,
                'hold_amount' => 0,
            ]);

            $this->logger->info('PaymentEngine: capture successful', [
                'correlation_id' => $correlationId,
                'payment_uuid' => $payment->uuid,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Refund payment.
     *
     * Flow:
     * 1. Validate payment status
     * 2. Gateway call (OUTSIDE transaction)
     * 3. DB transaction: credit wallet + update payment status
     *
     * @param string $paymentUuid
     * @param int $amount
     * @param string $reason
     * @param string|null $correlationId
     * @return PaymentTransaction
     */
    public function refund(
        string $paymentUuid,
        int $amount,
        string $reason = 'User requested',
        ?string $correlationId = null,
    ): PaymentTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $payment = PaymentTransaction::where('uuid', $paymentUuid)->firstOrFail();

        if ($payment->status !== 'captured') {
            throw new \RuntimeException("Payment must be captured to refund, current: {$payment->status}");
        }

        if ($amount > $payment->captured_amount) {
            throw new \RuntimeException("Refund amount exceeds captured amount");
        }

        $this->logger->info('PaymentEngine: refund started', [
            'correlation_id' => $correlationId,
            'payment_uuid' => $paymentUuid,
            'amount' => $amount,
        ]);

        // 1. Gateway call (OUTSIDE transaction - CRITICAL)
        try {
            $gatewayResponse = $this->gateway->refund($payment, $amount, $reason, $correlationId);
        } catch (\Throwable $e) {
            $this->logger->error('PaymentEngine: gateway refund failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // 2. DB transaction: credit wallet + update payment
        return $this->db->transaction(function () use ($payment, $amount, $reason, $correlationId) {
            $this->wallet->credit(
                walletId: $payment->wallet_id,
                amount: $amount,
                type: \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                correlationId: $correlationId,
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refunded_amount' => ($payment->refunded_amount ?? 0) + $amount,
                'refund_reason' => $reason,
            ]);

            $this->logger->info('PaymentEngine: refund successful', [
                'correlation_id' => $correlationId,
                'payment_uuid' => $payment->uuid,
            ]);

            return $payment->fresh();
        });
    }
}
