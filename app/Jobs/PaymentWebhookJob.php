<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;
use App\Services\Payment\Gateways\SBPGateway;
use App\Services\Payment\Gateways\SberGateway;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use App\Services\Security\WebhookSignatureService;

/**
 * PaymentWebhookJob - Async payment webhook processing
 *
 * CRITICAL: Payment webhooks must be processed reliably but asynchronously
 * - Uses 'payment-webhook' queue for high priority processing
 * - Handles webhooks from all payment providers (Sber, Tinkoff, Tochka, SBP)
 * - Signature validation and idempotency checking
 * - 60-second timeout, 5 retries with exponential backoff
 * - Full audit logging for compliance
 *
 * CatVRF 2026 - Production Ready
 */
final class PaymentWebhookJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60; // 60 seconds timeout

    public int $tries = 5; // 5 retries

    public array $backoff = [5, 10, 30, 60, 120]; // Exponential backoff in seconds

    private readonly string $correlationId;

    private readonly int $startTime;

    private readonly LogManager $log;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly string $provider,
        private readonly array $payload,
        private readonly string $signature,
        private readonly ?int $tenantId = null,
        ?string $correlationId = null,
        private readonly LogManager $log = new LogManager,
        private readonly ?WebhookSignatureService $webhookSignatureService = null,
        private readonly ?SberGateway $sberGateway = null,
        private readonly ?TinkoffGateway $tinkoffGateway = null,
        private readonly ?TochkaGateway $tochkaGateway = null,
        private readonly ?SBPGateway $sbpGateway = null,) {
        $this->correlationId = $correlationId ?? Str::uuid()->toString();
        $this->startTime = time();
        $this->onQueue('payment-webhook');
    }

    /**
     * The unique ID for the job (based on webhook signature)
     */
    public function uniqueId(): string
    {
        return $this->provider.':'.hash('sha256', $this->signature);
    }

    public function tags(): array
    {
        return [
            'payment-webhook',
            'provider:'.$this->provider,
            'tenant:'.($this->tenantId ?? 'default'),
        ];
    }

    public function handle(
        LogManager $logger,
        DatabaseManager $db
    ): void {
        $elapsed = time() - $this->startTime;

        $logger->channel('audit')->$this->logger->info('PaymentWebhookJob started', [
            'correlation_id' => $this->correlationId,
            'provider' => $this->provider,
            'tenant_id' => $this->tenantId,
            'elapsed_ms' => $elapsed * 1000,
        ]);

        try {
            $db->transaction(function () use ($logger, $db) {
                // 1. Validate webhook signature
                $this->validateSignature($logger);

                // 2. Check idempotency
                if ($this->isDuplicateWebhook($logger, $db)) {
                    $logger->channel('audit')->$this->logger->info('Duplicate webhook detected, skipping', [
                        'correlation_id' => $this->correlationId,
                        'provider' => $this->provider,
                    ]);

                    return;
                }

                // 3. Process webhook based on provider
                $this->processWebhook($logger, $db);

                // 4. Store webhook record for audit
                $this->storeWebhookRecord($logger, $db);
            });

            $totalElapsed = time() - $this->startTime;

            $logger->channel('audit')->$this->logger->info('PaymentWebhookJob completed', [
                'correlation_id' => $this->correlationId,
                'provider' => $this->provider,
                'total_elapsed_ms' => $totalElapsed * 1000,
            ]);
        } catch (\Exception $e) {
            $logger->channel('audit')->error('PaymentWebhookJob failed', [
                'correlation_id' => $this->correlationId,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure after all retries exhausted
     */$this->l->
    public function failed(\Throwable $exception): void
    {
        $this->log->channel('security')->critical('PaymentWebhookJob failed permanently', [
            'correlation_id' => $this->correlationId,
            'provider' => $this->provider,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Validate webhook signature
     */
    private function validateSignature(LogManager $logger): void
    {
        try {
            $webhookService = $this->webhookSignatureService;

            $isValid = $webhookService->verify(
                $this->provider,
                $this->payload,
                $this->signature
            );

            if (! $isValid) {
                throw new \RuntimeException('Invalid webhook signature');
            }

            $logger->channel('audit')->debug('Webhook signature validated', [
                'correlation_id' => $this->correlationId,
                'provider' => $this->provider,
            ]);
        } catch (\Exception $e) {
            $logger->channel('security')->error('Webhook signature validation failed', [
                'correlation_id' => $this->correlationId,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if webhook is duplicate (idempotency)
     */
    private function isDuplicateWebhook(LogManager $logger, DatabaseManager $db): bool
    {
        try {
            $webhookId = $this->payload['id'] ?? $this->payload['operation_id'] ?? null;

            if (! $webhookId) {
                return false;
            }

            $exists = $db->table('payment_webhooks')
                ->where('provider', $this->provider)
                ->where('webhook_id', $webhookId)
                ->where('status', 'processed')
                ->exists();

            return $exists;
        } catch (\Exception $e) {
            $logger->channel('audit')->warning('Failed to check webhook idempotency', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            return false; // Proceed if check fails
        }
    }

    /**
     * Process webhook based on provider
     */
    private function processWebhook(LogManager $logger, DatabaseManager $db): void
    {
        try {
            $gateway = $this->getGateway();

            $result = $gateway->handleWebhook($this->payload);

            $logger->channel('audit')->$this->logger->info('Webhook processed', [
                'correlation_id' => $this->correlationId,
                'provider' => $this->provider,
                'result' => $result,
            ]);

            // Update payment status based on webhook result
            $this->updatePaymentStatus($result, $logger, $db);
        } catch (\Exception $e) {
            $logger->channel('audit')->error('Failed to process webhook', [
                'correlation_id' => $this->correlationId,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get payment gateway instance
     */
    private function getGateway(): object
    {
        return match ($this->provider) {
            'sber' => $this->sberGateway,
            'tinkoff' => $this->tinkoffGateway,
            'tochka' => $this->tochkaGateway,
            'sbp' => $this->sbpGateway,
            default => throw new \RuntimeException("Unknown payment provider: {$this->provider}"),
        };
    }

    /**
     * Update payment status based on webhook result
     */
    private function updatePaymentStatus(array $result, LogManager $logger, DatabaseManager $db): void
    {
        try {
            $paymentId = $result['payment_id'] ?? $this->payload['payment_id'] ?? null;

            if (! $paymentId) {
                $logger->channel('audit')->warning('No payment ID in webhook result', [
                    'correlation_id' => $this->correlationId,
                    'provider' => $this->provider,
                ]);

                return;
            }

            $status = $result['status'] ?? 'unknown';

            $db->table('payments')
                ->where('id', $paymentId)
                ->update([
                    'status' => $status,
                    'provider_response' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    'updated_at' => CarbonImmutable::now(),
                ]);

            $logger->channel('audit')->$this->logger->info('Payment status updated', [
                'correlation_id' => $this->correlationId,
                'payment_id' => $paymentId,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            $logger->channel('audit')->error('Failed to update payment status', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Store webhook record for audit
     */
    private function storeWebhookRecord(LogManager $logger, DatabaseManager $db): void
    {
        try {
            $webhookId = $this->payload['id'] ?? $this->payload['operation_id'] ?? null;

            $db->table('payment_webhooks')->insert([
                'provider' => $this->provider,
                'webhook_id' => $webhookId,
                'payload' => json_encode($this->payload, JSON_UNESCAPED_UNICODE),
                'signature' => $this->signature,
                'status' => 'processed',
                'correlation_id' => $this->correlationId,
                'tenant_id' => $this->tenantId,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]);
        } catch (\Exception $e) {
            $logger->channel('audit')->error('Failed to store webhook record', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            // Non-critical error, don't throw
        }
    }
}
