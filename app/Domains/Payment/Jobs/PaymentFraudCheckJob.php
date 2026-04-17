<?php

declare(strict_types=1);

namespace App\Domains\Payment\Jobs;

use App\Domains\FraudML\DTOs\FraudMLOperationDto;
use App\Domains\FraudML\Services\FraudMLService;
use App\Domains\Payment\Models\PaymentRecord;
use App\Domains\Payment\Enums\PaymentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * PaymentFraudCheckJob - Async fraud detection for payments.
 *
 * CRITICAL: FraudML inference moved to async job to prevent blocking payment flow.
 * Uses rule-based fallback if ML service fails.
 *
 * Architecture:
 * - Dedicated queue: payment-fraud-check
 * - Unique by payment_id to prevent duplicate checks
 * - 30 second timeout
 * - Updates payment status if fraud detected
 *
 * @package App\Domains\Payment\Jobs
 */
final readonly class PaymentFraudCheckJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 30;
    public string $queue = 'payment-fraud-check';

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 3600;

    public function __construct(
        public int $paymentId,
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "payment_fraud_check_{$this->paymentId}";
    }

    /**
     * Execute the job.
     */
    public function handle(FraudMLService $fraudML, LoggerInterface $logger): void
    {
        /** @var PaymentRecord $payment */
        $payment = PaymentRecord::with(['wallet'])->findOrFail($this->paymentId);

        // Skip if already processed
        if (in_array($payment->status, [PaymentStatus::COMPLETED, PaymentStatus::CANCELLED, PaymentStatus::FRAUD_BLOCKED], true)) {
            $logger->info('Payment fraud check skipped - already processed', [
                'payment_id' => $this->paymentId,
                'status' => $payment->status->value,
            ]);

            return;
        }

        // Build operation DTO for ML inference
        $operationDto = new FraudMLOperationDto(
            tenant_id: $payment->tenant_id,
            user_id: $payment->user_id,
            operation_type: 'payment_create',
            amount: $payment->amount_kopecks,
            ip_address: $payment->metadata['ip_address'] ?? '',
            device_fingerprint: $payment->metadata['device_fingerprint'] ?? '',
            correlation_id: $payment->correlation_id,
            vertical_code: $payment->vertical_code ?? 'default',
            current_quota_usage_ratio: $this->getQuotaUsageRatio($payment->tenant_id),
            additional_context: [
                'payment_id' => $payment->id,
                'provider_code' => $payment->provider_code,
                'wallet_id' => $payment->wallet_id,
            ],
        );

        try {
            $fraudScore = $fraudML->scoreOperation($operationDto);
            $shouldBlock = $fraudML->shouldBlock($fraudScore, 'payment_create');

            $logger->info('Payment fraud check completed', [
                'payment_id' => $this->paymentId,
                'fraud_score' => $fraudScore,
                'should_block' => $shouldBlock,
                'correlation_id' => $payment->correlation_id,
            ]);

            if ($shouldBlock) {
                $this->blockPayment($payment, $fraudScore, $logger);
            }

        } catch (\Exception $e) {
            // Rule-based fallback on ML failure
            $logger->warning('Payment fraud ML check failed, using rule-based fallback', [
                'payment_id' => $this->paymentId,
                'error' => $e->getMessage(),
                'correlation_id' => $payment->correlationId,
            ]);

            // Apply conservative rule-based checks
            $this->applyRuleBasedChecks($payment, $logger);
        }
    }

    /**
     * Block payment due to fraud detection.
     */
    private function blockPayment(PaymentRecord $payment, float $fraudScore, LoggerInterface $logger): void
    {
        DB::transaction(function () use ($payment, $fraudScore, $logger) {
            $payment->update([
                'status' => 'fraud_blocked',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'fraud_blocked' => true,
                    'fraud_score' => $fraudScore,
                    'fraud_blocked_at' => now()->toIso8601String(),
                ]),
            ]);

            $logger->warning('Payment blocked due to fraud detection', [
                'payment_id' => $payment->id,
                'fraud_score' => $fraudScore,
                'correlation_id' => $payment->correlation_id,
            ]);

            // TODO: Dispatch PaymentFraudBlocked event for notification
            // TODO: Release wallet hold if exists
        });
    }

    /**
     * Apply rule-based fraud checks as fallback.
     */
    private function applyRuleBasedChecks(PaymentRecord $payment, LoggerInterface $logger): void
    {
        $threshold = config('payment.fraud.amount_threshold', 5000000); // 50,000 RUB in kopecks

        // Block if amount exceeds threshold
        if ($payment->amount_kopecks > $threshold) {
            $this->blockPayment($payment, 1.0, $logger);
            $logger->info('Payment blocked by rule-based check (amount threshold)', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount_kopecks,
                'threshold' => $threshold,
            ]);
            return;
        }

        // Additional rule-based checks can be added here
        // - Velocity checks (too many payments in short time)
        // - Geolocation checks
        // - Device fingerprint checks
    }

    /**
     * Get quota usage ratio for tenant.
     */
    private function getQuotaUsageRatio(int $tenantId): float
    {
        // TODO: Integrate with TenantQuotaService
        // For now, return conservative estimate
        return 0.5;
    }
}
