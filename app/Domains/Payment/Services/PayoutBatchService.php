<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\Payout;
use App\Domains\Payment\Models\PayoutBatch;
use App\Domains\Payment\Services\Gateways\TinkoffAcquiringAdapter;
use App\Domains\Payment\Services\Gateways\TochkaBankAdapter;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Payout Batch Service - manages mass seller payouts.
 *
 * Features:
 * - Create payout batches from seller balances
 * - Process batches through payment gateways
 * - Track batch progress and failures
 * - Support for scheduled payouts
 * - Automatic retry for failed payouts
 * - Detailed audit logging
 */
final readonly class PayoutBatchService
{
    private const int BATCH_SIZE = 100;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly PayoutService $payoutService,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
        private readonly TinkoffAcquiringAdapter $tinkoffAdapter,
        private readonly TochkaBankAdapter $tochkaAdapter,
    ) {}

    /**
     * Create payout batch from pending payouts.
     */
    public function createBatchFromPending(
        string $provider = 'tinkoff',
        ?\DateTime $scheduledAt = null,
        ?string $correlationId = null,
    ): PayoutBatch {
        $correlationId ??= Str::uuid()->toString();

        $pendingPayouts = Payout::where('status', 'pending')
            ->limit(self::BATCH_SIZE)
            ->get();

        if ($pendingPayouts->isEmpty()) {
            throw new \RuntimeException('No pending payouts to batch');
        }

        $this->logger->info('Creating payout batch', [
            'payout_count' => $pendingPayouts->count(),
            'provider' => $provider,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($pendingPayouts, $provider, $scheduledAt, $correlationId) {
            $totalAmount = $pendingPayouts->sum('amount_kopecks');

            $batch = PayoutBatch::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
                'business_group_id' => function_exists('tenant') && tenant() ? tenant()->business_group_id : null,
                'provider' => $provider,
                'status' => 'pending',
                'total_amount_kopecks' => $totalAmount,
                'total_count' => $pendingPayouts->count(),
                'processed_count' => 0,
                'failed_count' => 0,
                'currency' => 'RUB',
                'scheduled_at' => $scheduledAt,
                'correlation_id' => $correlationId,
            ]);

            // Link payouts to batch
            foreach ($pendingPayouts as $payout) {
                $payout->update(['batch_id' => $batch->id]);
            }

            $this->audit->logAction(
                action: 'payout_batch_created',
                subjectType: PayoutBatch::class,
                subjectId: $batch->id,
                newValues: [
                    'provider' => $provider,
                    'total_amount_kopecks' => $totalAmount,
                    'total_count' => $pendingPayouts->count(),
                ],
                correlationId: $correlationId,
            );

            return $batch;
        });
    }

    /**
     * Create payout batch from custom data.
     */
    public function createBatch(
        array $payouts,
        string $provider = 'tinkoff',
        ?\DateTime $scheduledAt = null,
        ?string $correlationId = null,
    ): PayoutBatch {
        $correlationId ??= Str::uuid()->toString();

        $this->logger->info('Creating custom payout batch', [
            'payout_count' => count($payouts),
            'provider' => $provider,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($payouts, $provider, $scheduledAt, $correlationId) {
            $totalAmount = array_sum(array_column($payouts, 'amount_kopecks'));

            $batch = PayoutBatch::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
                'business_group_id' => function_exists('tenant') && tenant() ? tenant()->business_group_id : null,
                'provider' => $provider,
                'status' => 'pending',
                'total_amount_kopecks' => $totalAmount,
                'total_count' => count($payouts),
                'processed_count' => 0,
                'failed_count' => 0,
                'currency' => 'RUB',
                'scheduled_at' => $scheduledAt,
                'correlation_id' => $correlationId,
            ]);

            // Create payout records
            foreach ($payouts as $payoutData) {
                $payout = $this->payoutService->createPayout(
                    sellerId: $payoutData['seller_id'],
                    paymentRecordId: $payoutData['payment_record_id'] ?? null,
                    amountKopecks: $payoutData['amount_kopecks'],
                    providerCode: $provider,
                    correlationId: $correlationId,
                    metadata: $payoutData['metadata'] ?? [],
                );
                $payout->update(['batch_id' => $batch->id]);
            }

            $this->audit->logAction(
                action: 'payout_batch_created_custom',
                subjectType: PayoutBatch::class,
                subjectId: $batch->id,
                newValues: [
                    'provider' => $provider,
                    'total_amount_kopecks' => $totalAmount,
                    'total_count' => count($payouts),
                ],
                correlationId: $correlationId,
            );

            return $batch;
        });
    }

    /**
     * Process payout batch.
     */
    public function processBatch(PayoutBatch $batch, ?string $correlationId = null): PayoutBatch
    {
        $correlationId ??= Str::uuid()->toString();

        if (! $batch->isReadyToProcess()) {
            throw new \InvalidArgumentException('Batch is not ready to process');
        }

        $this->logger->info('Processing payout batch', [
            'batch_id' => $batch->id,
            'total_count' => $batch->total_count,
            'correlation_id' => $correlationId,
        ]);

        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $gateway = $this->getGateway($batch->provider);

        foreach ($batch->payouts as $payout) {
            try {
                $this->processSinglePayout($payout, $gateway, $correlationId);
                $batch->increment('processed_count');
            } catch (\Throwable $e) {
                $this->logger->error('Payout failed', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);
                $batch->increment('failed_count');
                
                // Update payout status to failed
                $this->payoutService->updateStatus(
                    payoutId: $payout->id,
                    status: 'failed',
                    providerResponse: ['error' => $e->getMessage()],
                );
            }
        }

        // Update batch status
        $batch->update([
            'status' => $batch->failed_count > 0 ? 'partial' : 'completed',
            'completed_at' => now(),
        ]);

        $this->audit->logAction(
            action: 'payout_batch_processed',
            subjectType: PayoutBatch::class,
            subjectId: $batch->id,
            newValues: [
                'processed_count' => $batch->processed_count,
                'failed_count' => $batch->failed_count,
                'status' => $batch->status,
            ],
            correlationId: $correlationId,
        );

        return $batch->fresh();
    }

    /**
     * Process single payout through gateway.
     */
    private function processSinglePayout(Payout $payout, mixed $gateway, string $correlationId): void
    {
        $this->payoutService->updateStatus(
            payoutId: $payout->id,
            status: 'processing',
        );

        // Prepare gateway-specific payload
        $payload = $this->prepareGatewayPayload($payout, $gateway);

        // Call gateway
        if ($gateway instanceof TinkoffAcquiringAdapter) {
            $response = $gateway->initiatePayout($payload);
        } elseif ($gateway instanceof TochkaBankAdapter) {
            $response = $gateway->initiatePayment($payload);
        } else {
            throw new \InvalidArgumentException('Unsupported gateway');
        }

        // Update payout with response
        $this->payoutService->updateStatus(
            payoutId: $payout->id,
            status: 'completed',
            providerPayoutId: $response['provider_payout_id'] ?? $response['payout_id'] ?? null,
            providerResponse: $response,
        );
    }

    /**
     * Prepare gateway-specific payload.
     */
    private function prepareGatewayPayload(Payout $payout, mixed $gateway): array
    {
        $seller = $payout->seller;

        if ($gateway instanceof TinkoffAcquiringAdapter) {
            return [
                'amount' => $payout->amount_kopecks,
                'order_id' => $payout->uuid,
                'description' => "Payout to seller #{$seller->id}",
                'account_number' => $seller->bank_account ?? throw new \InvalidArgumentException('Seller bank account required'),
            ];
        }

        if ($gateway instanceof TochkaBankAdapter) {
            return [
                'amount' => $payout->amount_kopecks,
                'payer_account' => config('payment.tochka.payer_account'),
                'payee_account' => $seller->bank_account ?? throw new \InvalidArgumentException('Seller bank account required'),
                'payee_inn' => $seller->inn ?? throw new \InvalidArgumentException('Seller INN required'),
                'payee_name' => $seller->company_name ?? $seller->name,
                'payment_purpose' => "Payout #{$payout->uuid}",
            ];
        }

        throw new \InvalidArgumentException('Unsupported gateway');
    }

    /**
     * Retry failed payouts in batch.
     */
    public function retryFailedPayouts(PayoutBatch $batch, ?string $correlationId = null): PayoutBatch
    {
        $correlationId ??= Str::uuid()->toString();

        $failedPayouts = $batch->payouts()->where('status', 'failed')->get();

        if ($failedPayouts->isEmpty()) {
            return $batch;
        }

        $this->logger->info('Retrying failed payouts', [
            'batch_id' => $batch->id,
            'failed_count' => $failedPayouts->count(),
            'correlation_id' => $correlationId,
        ]);

        $gateway = $this->getGateway($batch->provider);

        foreach ($failedPayouts as $payout) {
            try {
                $this->processSinglePayout($payout, $gateway, $correlationId);
                $batch->increment('processed_count');
                $batch->decrement('failed_count');
            } catch (\Throwable $e) {
                $this->logger->error('Payout retry failed', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update batch status if all successful
        if ($batch->failed_count === 0) {
            $batch->update(['status' => 'completed']);
        }

        return $batch->fresh();
    }

    /**
     * Get batches ready to process.
     */
    public function getReadyBatches(): Collection
    {
        return PayoutBatch::readyToProcess()->get();
    }

    /**
     * Get batch by UUID.
     */
    public function getByUuid(string $uuid): ?PayoutBatch
    {
        return PayoutBatch::where('uuid', $uuid)->first();
    }

    /**
     * Cancel batch (only pending batches).
     */
    public function cancelBatch(PayoutBatch $batch, ?string $correlationId = null): PayoutBatch
    {
        $correlationId ??= Str::uuid()->toString();

        if ($batch->status !== 'pending') {
            throw new \InvalidArgumentException('Only pending batches can be canceled');
        }

        $batch->update([
            'status' => 'canceled',
            'failed_at' => now(),
        ]);

        // Release payouts from batch
        $batch->payouts()->update(['batch_id' => null]);

        $this->audit->logAction(
            action: 'payout_batch_canceled',
            subjectType: PayoutBatch::class,
            subjectId: $batch->id,
            newValues: [],
            correlationId: $correlationId,
        );

        return $batch->fresh();
    }

    /**
     * Get batch statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_batches' => PayoutBatch::count(),
            'pending_batches' => PayoutBatch::where('status', 'pending')->count(),
            'processing_batches' => PayoutBatch::where('status', 'processing')->count(),
            'completed_batches' => PayoutBatch::where('status', 'completed')->count(),
            'failed_batches' => PayoutBatch::where('status', 'failed')->count(),
            'total_amount_processed' => PayoutBatch::where('status', 'completed')->sum('total_amount_kopecks'),
            'total_payouts_processed' => PayoutBatch::where('status', 'completed')->sum('total_count'),
        ];
    }

    private function getGateway(string $provider): mixed
    {
        return match ($provider) {
            'tinkoff' => $this->tinkoffAdapter,
            'tochka' => $this->tochkaAdapter,
            default => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };
    }
}
