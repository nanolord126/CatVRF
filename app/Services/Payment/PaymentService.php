<?php declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Services\FraudControlService;
use App\Services\Security\IdempotencyService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class PaymentService
{
    public function __construct(
        private IdempotencyService $idempotency,
        private FraudControlService $fraudControl,
        private WalletService $walletService,
    ) {}

    /**
     * Инициация платежа с hold или сразу capture
     */
    public function initPayment(
        int $amount,
        int $tenantId,
        int $userId,
        string $paymentMethod = 'card',
        bool $hold = true,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        array $metadata = [],
    ): PaymentTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $idempotencyKey = $idempotencyKey ?? Str::uuid()->toString();

        // Проверка idempotency
        $existingPayment = $this->idempotency->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: compact('amount', 'tenantId', 'userId', 'paymentMethod', 'hold', 'metadata'),
        );

        if ($existingPayment) {
            Log::channel('audit')->info('Payment already exists (idempotency)', [
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
                'existing_payment_id' => $existingPayment['payment_id'] ?? null,
            ]);

            return PaymentTransaction::where('uuid', $existingPayment['payment_id'])->firstOrFail();
        }

        // Fraud check
        $fraudResult = $this->fraudControl->check(
            userId: $userId,
            operationType: 'payment_init',
            amount: $amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Payment init blocked by fraud check', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'fraud_score' => $fraudResult['score'],
            ]);

            throw new \RuntimeException('Payment blocked by fraud detection system');
        }

        return $this->db->transaction(function () use (
            $amount,
            $tenantId,
            $userId,
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
                'provider' => 'tinkoff', // или динамически
                'provider_code' => 'tinkoff',
                'status' => $hold ? PaymentTransaction::STATUS_AUTHORIZED : PaymentTransaction::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'currency' => 'RUB',
                'hold' => $hold,
                'hold_amount' => $hold ? $amount : 0,
                'authorized_at' => $hold ? now() : null,
                'correlation_id' => $correlationId,
                'ip_address' => request()->ip(),
                'device_fingerprint' => request()->header('X-Device-Fingerprint'),
                'fraud_score' => $fraudResult['score'],
                'metadata' => $metadata,
                'tags' => ['payment_init' => true],
            ]);

            // Сохранение idempotency record
            $this->idempotency->store(
                operation: 'payment_init',
                idempotencyKey: $idempotencyKey,
                payload: compact('amount', 'tenantId', 'userId', 'paymentMethod', 'hold', 'metadata'),
                responseData: ['payment_id' => $payment->uuid],
            );

            Log::channel('audit')->info('Payment initialized', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'payment_uuid' => $payment->uuid,
                'amount' => $amount,
                'hold' => $hold,
                'fraud_score' => $fraudResult['score'],
            ]);

            return $payment;
        });
    }

    /**
     * Capture платежа (списание после hold)
     */
    public function capture(
        string $paymentUuid,
        ?int $amount = null,
        ?string $correlationId = null,
    ): PaymentTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        return $this->db->transaction(function () use ($paymentUuid, $amount, $correlationId) {
            $payment = PaymentTransaction::where('uuid', $paymentUuid)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentTransaction::STATUS_AUTHORIZED) {
                throw new \RuntimeException("Payment must be in authorized status to capture, current: {$payment->status}");
            }

            $captureAmount = $amount ?? $payment->hold_amount;

            if ($captureAmount > $payment->hold_amount) {
                throw new \RuntimeException("Capture amount {$captureAmount} exceeds hold amount {$payment->hold_amount}");
            }

            // Зачисление на wallet
            $this->walletService->credit(
                tenantId: $payment->tenant_id,
                amount: $captureAmount,
                type: 'payment_capture',
                sourceId: $payment->id,
                correlationId: $correlationId,
                reason: "Payment capture {$payment->uuid}",
                sourceType: 'payment',
                walletId: $payment->wallet_id,
            );

            $payment->update([
                'status' => PaymentTransaction::STATUS_CAPTURED,
                'captured_at' => now(),
                'hold_amount' => $payment->hold_amount - $captureAmount,
            ]);

            Log::channel('audit')->info('Payment captured', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'payment_uuid' => $payment->uuid,
                'capture_amount' => $captureAmount,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Refund платежа (возврат)
     */
    public function refund(
        string $paymentUuid,
        int $amount,
        string $reason,
        ?string $correlationId = null,
    ): PaymentTransaction {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        return $this->db->transaction(function () use ($paymentUuid, $amount, $reason, $correlationId) {
            $payment = PaymentTransaction::where('uuid', $paymentUuid)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentTransaction::STATUS_CAPTURED) {
                throw new \RuntimeException("Payment must be captured to refund, current: {$payment->status}");
            }

            if ($amount > $payment->amount) {
                throw new \RuntimeException("Refund amount {$amount} exceeds original payment amount {$payment->amount}");
            }

            // 1. Возврат в Wallet (увеличение баланса)
            $this->walletService->credit(
                tenantId: $payment->tenant_id,
                amount: $amount,
                type: 'refund',
                sourceId: $payment->id,
                correlationId: $correlationId,
                reason: "Refund for payment {$payment->uuid}: {$reason}",
                sourceType: 'payment',
                walletId: $payment->wallet_id,
            );

            // 2. Вызов провайдера (имитация или драйвер Tinkoff/Sber)
            // $gateway->refund($payment->provider_payment_id, $amount);

            $payment->update([
                'status' => PaymentTransaction::STATUS_REFUNDED,
                'refunded_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'refund_reason' => $reason,
                    'refunded_amount' => $amount,
                    'refund_correlation_id' => $correlationId,
                ]),
            ]);

            Log::channel('audit')->info('Payment refunded', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'payment_uuid' => $payment->uuid,
                'refund_amount' => $amount,
                'reason' => $reason,
            ]);

            return $payment->fresh();
        });
    }
}
            }

            if ($amount > $payment->amount) {
                throw new \RuntimeException("Refund amount {$amount} exceeds payment amount {$payment->amount}");
            }

            // Возврат на wallet (увеличение баланса)
            $this->walletService->credit(
                tenantId: $payment->tenant_id,
                amount: $amount,
                type: 'refund',
                sourceId: $payment->id,
                correlationId: $correlationId,
                reason: $reason,
                sourceType: 'payment_refund',
                walletId: $payment->wallet_id,
            );

            $payment->update([
                'status' => PaymentTransaction::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);

            Log::channel('audit')->info('Payment refunded', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'payment_uuid' => $payment->uuid,
                'refund_amount' => $amount,
                'reason' => $reason,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Обработка webhook от платежной системы
     */
    public function handleWebhook(
        string $provider,
        array $payload,
        ?string $signature = null,
    ): bool {
        $correlationId = Str::uuid()->toString();
        // if (!$this->webhookSignature->verify($provider, $payload, $signature)) {
        //     Log::channel('webhook_errors')->error('Webhook signature verification failed', [
        //         'correlation_id' => $correlationId,
        //         'provider' => $provider,
        //     ]);
        //     return false;
        // }

        Log::channel('audit')->info('Webhook received', [
            'correlation_id' => $correlationId,
            'provider' => $provider,
            'payload' => $payload,
        ]);

        // Обработка webhook в зависимости от провайдера

        return true;
    }
}
