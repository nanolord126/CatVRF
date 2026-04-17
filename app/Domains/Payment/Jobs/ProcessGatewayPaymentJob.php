<?php declare(strict_types=1);

namespace App\Domains\Payment\Jobs;

use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentIdempotencyService;
use App\Services\Payment\Gateways\PaymentGatewayInterface;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\SberGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Process Gateway Payment Job
 * 
 * Handles external payment gateway calls asynchronously to avoid
 * holding DB transactions during slow external API calls.
 * 
 * This job:
 * - Calls external gateway API
 * - Updates payment transaction status
 * - Handles retries on transient failures
 * - Logs all operations with correlation_id
 */
final class ProcessGatewayPaymentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120; // 2 minutes for gateway timeout
    public $backoff = [5, 15, 30]; // Exponential backoff: 5s, 15s, 30s

    public function __construct(
        public readonly int $paymentTransactionId,
        public readonly string $provider,
        public readonly string $correlationId,
    ) {
        $this->onQueue('payments-high-priority');
    }

    /**
     * Unique ID for job to prevent duplicate processing
     */
    public function uniqueId(): string
    {
        return "payment:{$this->paymentTransactionId}:{$this->provider}";
    }

    /**
     * Execute the job
     */
    public function handle(
        TinkoffGateway $tinkoff,
        SberGateway $sber,
        TochkaGateway $tochka,
        PaymentIdempotencyService $idempotency,
    ): void {
        $payment = PaymentTransaction::whereKey($this->paymentTransactionId)->firstOrFail();

        Log::channel('audit')->info('Processing gateway payment job', [
            'correlation_id' => $this->correlationId,
            'payment_id' => $payment->id,
            'payment_uuid' => $payment->uuid,
            'provider' => $this->provider,
            'attempt' => $this->attempts(),
        ]);

        // Check if already processed (idempotency)
        if (in_array($payment->status, [
            PaymentTransaction::STATUS_CAPTURED,
            PaymentTransaction::STATUS_AUTHORIZED,
            PaymentTransaction::STATUS_FAILED,
        ])) {
            Log::channel('audit')->info('Payment already processed, skipping', [
                'correlation_id' => $this->correlationId,
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
            ]);

            return;
        }

        // Get appropriate gateway
        $gateway = match($this->provider) {
            'tinkoff' => $tinkoff,
            'sber' => $sber,
            'tochka' => $tochka,
            default => throw new Exception("Unknown payment provider: {$this->provider}"),
        };

        try {
            // Call gateway API
            $result = $gateway->initPayment([
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'order_id' => $payment->uuid,
                'description' => $payment->metadata['description'] ?? 'Payment',
                'customer_key' => $payment->user_id,
                'idempotency_key' => $payment->idempotency_key,
            ]);

            // Update payment transaction with gateway response
            $payment->update([
                'provider_payment_id' => $result['payment_id'] ?? null,
                'provider_status' => $result['status'] ?? 'pending',
                'payment_url' => $result['payment_url'] ?? null,
                'status' => $this->mapGatewayStatusToPaymentStatus($result['status'] ?? 'pending'),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'gateway_response' => $result,
                    'processed_at' => now()->toIso8601String(),
                ]),
            ]);

            // Store idempotency record
            $idempotency->store(
                operation: 'payment_init',
                idempotencyKey: $payment->idempotency_key,
                payload: [
                    'amount' => $payment->amount,
                    'tenant_id' => $payment->tenant_id,
                    'user_id' => $payment->user_id,
                ],
                response: $result,
                tenantId: $payment->tenant_id,
            );

            Log::channel('audit')->info('Gateway payment processed successfully', [
                'correlation_id' => $this->correlationId,
                'payment_id' => $payment->id,
                'provider' => $this->provider,
                'provider_payment_id' => $result['payment_id'] ?? null,
                'status' => $payment->status,
            ]);

        } catch (Exception $e) {
            // Mark payment as failed on final attempt
            if ($this->attempts() >= $this->tries) {
                $payment->update([
                    'status' => PaymentTransaction::STATUS_FAILED,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::channel('audit')->error('Gateway payment failed after all retries', [
                    'correlation_id' => $this->correlationId,
                    'payment_id' => $payment->id,
                    'provider' => $this->provider,
                    'error' => $e->getMessage(),
                    'attempts' => $this->attempts(),
                ]);

                throw $e;
            }

            // Log retry attempt
            Log::channel('audit')->warning('Gateway payment failed, retrying', [
                'correlation_id' => $this->correlationId,
                'payment_id' => $payment->id,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'next_retry_in' => $this->backoff[$this->attempts() - 1] ?? 30,
            ]);

            throw $e;
        }
    }

    /**
     * Map gateway status to internal payment status
     */
    private function mapGatewayStatusToPaymentStatus(string $gatewayStatus): string
    {
        return match(strtolower($gatewayStatus)) {
            'success', 'succeeded', 'completed', 'captured' => PaymentTransaction::STATUS_CAPTURED,
            'authorized', 'hold' => PaymentTransaction::STATUS_AUTHORIZED,
            'pending', 'processing', 'in_progress' => PaymentTransaction::STATUS_PENDING,
            'failed', 'rejected', 'cancelled' => PaymentTransaction::STATUS_FAILED,
            default => PaymentTransaction::STATUS_PENDING,
        };
    }
}
