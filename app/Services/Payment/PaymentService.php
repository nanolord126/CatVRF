<?php declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Http\Request;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Services\FraudControlService;
use App\Domains\Payment\Jobs\ProcessGatewayPaymentJob;
use App\Domains\Payment\Jobs\ProcessCaptureJob;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class PaymentService
{
    public function __construct(
        private readonly Request $request,
        private IdempotencyService $idempotency,
        private FraudControlService $fraud,
        private WalletService $walletService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
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
            $this->logger->channel('audit')->info('Payment already exists (idempotency)', [
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
                'existing_payment_id' => $existingPayment['payment_id'] ?? null,
            ]);

            return PaymentTransaction::where('uuid', $existingPayment['payment_id'])->firstOrFail();
        }

        // Fraud check
        $fraudResult = $this->fraud->check(
            userId: $userId,
            operationType: 'payment_init',
            amount: $amount,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->logger->channel('fraud_alert')->warning('Payment init blocked by fraud check', [
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
                'ip_address' => $this->request->ip(),
                'device_fingerprint' => $this->request->header('X-Device-Fingerprint'),
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

            $this->logger->channel('audit')->info('Payment initialized', [
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

        $payment = PaymentTransaction::where('uuid', $paymentUuid)->firstOrFail();

        if ($payment->status !== PaymentTransaction::STATUS_AUTHORIZED) {
            throw new \RuntimeException("Payment must be in authorized status to capture, current: {$payment->status}");
        }

        $captureAmount = $amount ?? $payment->hold_amount;

        if ($captureAmount > $payment->hold_amount) {
            throw new \RuntimeException("Capture amount {$captureAmount} exceeds hold amount {$payment->hold_amount}");
        }

        // Dispatch async job for capture
        ProcessCaptureJob::dispatch(
            $paymentUuid,
            $captureAmount,
            $correlationId,
        );

        $this->logger->channel('audit')->info('Capture job dispatched', [
            'correlation_id' => $correlationId,
            'payment_uuid' => $paymentUuid,
            'amount' => $captureAmount,
        ]);

        return $payment;
    }

    /**
     * Возврат платежа (полный или частичный)
     */
    public function refund(
        string $paymentUuid,
        int $amount,
        string $reason = '',
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
                'refunded_amount' => $amount,
                'refund_reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Обработка webhook от платёжного провайдера
     */
    public function handleWebhook(string $provider, array $payload, ?string $correlationId = null): bool
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->logger->channel('audit')->info('Payment webhook received', [
            'correlation_id' => $correlationId,
            'provider' => $provider,
            'payload' => $payload,
        ]);

        // Обработка webhook в зависимости от провайдера

        return true;
    }
}
