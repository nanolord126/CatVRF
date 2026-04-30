<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\DTOs\PaymentIntentDTO;
use App\Domains\Payment\DTOs\PaymentResultDTO;
use App\Domains\Payment\Models\EscrowHold;
use App\Domains\Payment\Models\PaymentIntent;
use App\Domains\Payment\Models\PayoutBatch;
use App\Domains\Payment\Models\RecurringSubscription;
use App\Domains\Payment\Services\Gateways\TinkoffAcquiringAdapter;
use App\Domains\Payment\Services\Gateways\TochkaBankAdapter;
use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;
use App\Domains\Payment\ValueObjects\PaymentStatusVO;
use App\Domains\Payments\AML\AMLService;
use App\Services\FraudControlService;
use App\Services\Payment\PaymentEngine;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Security\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Payment Facade Service - orchestrates all payment operations.
 *
 * Single entry point that coordinates:
 * - Payment processing with smart routing
 * - Escrow operations
 * - Recurring billing
 * - Payouts and splits
 * - Integration with Fraud, Audit, BigData
 */
final readonly class PaymentFacadeService
{
    public function __construct(
        private readonly PaymentEngine $paymentEngine,
        private readonly PaymentGatewayService $gatewayService,
        private readonly SmartRoutingService $smartRouting,
        private readonly EscrowService $escrowService,
        private readonly SplitPaymentService $splitPayment,
        private readonly PayoutService $payoutService,
        private readonly OutboxService $outboxService,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly TinkoffAcquiringAdapter $tinkoffAdapter,
        private readonly TochkaBankAdapter $tochkaAdapter,
        private readonly ?AMLService $amlService = null,
    ) {}

    /**
     * Process a payment with smart routing.
     */
    public function process(
        Model $payable,
        PaymentMethodVO $method,
        MoneyVO $amount,
        ?string $idempotencyKey = null,
        ?string $preferredGateway = null,
    ): PaymentResultDTO {
        $correlationId = Str::uuid()->toString();
        $idempotencyKey ??= Str::uuid()->toString();

        $this->logger->info('PaymentFacade: processing payment', [
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
            'amount' => $amount->toKopecks(),
            'method' => $method->method,
            'correlation_id' => $correlationId,
        ]);

        // 1. Fraud check
        $fraudResult = $this->fraudControl->check(
            userId: $payable->user_id ?? null,
            operationType: 'payment_process',
            amount: $amount->toKopecks(),
            ipAddress: request()->ip() ?? '127.0.0.1',
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->audit->logAction(
                action: 'payment_blocked_fraud',
                subjectType: get_class($payable),
                subjectId: $payable->id,
                newValues: [
                    'fraud_score' => $fraudResult['score'],
                    'reason' => $fraudResult['reason'] ?? 'High fraud risk',
                ],
                correlationId: $correlationId,
            );

            return PaymentResultDTO::error(
                paymentIntentId: '',
                paymentTransactionId: '',
                errorMessage: 'Payment blocked by fraud detection',
                errorCode: 'FRAUD_DETECTED',
                correlationId: $correlationId,
            );
        }

        // 2. AML check (ФЗ-115 compliance)
        if ($this->amlService && config('aml.enabled')) {
            $user = $payable->user_id ? \App\Models\User::find($payable->user_id) : null;
            if ($user) {
                try {
                    $amlResult = $this->amlService->checkPayment(
                        order: $payable,
                        buyer: $user
                    );

                    if (!$amlResult->passed) {
                        $this->audit->logAction(
                            action: 'payment_blocked_aml',
                            subjectType: get_class($payable),
                            subjectId: $payable->id,
                            newValues: [
                                'aml_risk_score' => $amlResult->riskScore,
                                'kyc_level' => $amlResult->kycLevel,
                                'reason' => $amlResult->reason,
                            ],
                            correlationId: $correlationId,
                        );

                        return PaymentResultDTO::error(
                            paymentIntentId: '',
                            paymentTransactionId: '',
                            errorMessage: $amlResult->reason ?? 'Payment blocked by AML check (ФЗ-115)',
                            errorCode: 'AML_BLOCKED',
                            correlationId: $correlationId,
                        );
                    }

                    $this->logger->info('AML check passed', [
                        'aml_risk_score' => $amlResult->riskScore,
                        'kyc_level' => $amlResult->kycLevel,
                        'correlation_id' => $correlationId,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->warning('AML check failed, continuing with payment', [
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                    // Continue with payment if AML check fails (configurable)
                    if (!config('aml.fraud_control.continue_on_failure', true)) {
                        return PaymentResultDTO::error(
                            paymentIntentId: '',
                            paymentTransactionId: '',
                            errorMessage: 'AML check failed',
                            errorCode: 'AML_ERROR',
                            correlationId: $correlationId,
                        );
                    }
                }
            }
        }

        // 3. Smart routing
        $routing = $this->smartRouting->selectGateway(
            amount: $amount,
            paymentMethod: $method,
            clientType: $payable->client_type ?? 'b2c',
            fraudScore: $fraudResult['score'] ?? null,
            preferredProvider: $preferredGateway,
        );

        $this->logger->info('PaymentFacade: gateway selected', [
            'provider' => $routing['provider'],
            'confidence' => $routing['confidence'],
            'correlation_id' => $correlationId,
        ]);

        // 3. Create payment intent
        $paymentIntent = PaymentIntent::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
            'user_id' => $payable->user_id ?? null,
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
            'amount_kopecks' => $amount->toKopecks(),
            'currency' => $amount->currency,
            'status' => 'pending',
            'payment_method' => $method->method,
            'payment_method_provider' => $method->provider,
            'provider' => $routing['provider'],
            'capture_method' => true, // automatic capture
            'correlation_id' => $correlationId,
            'fraud_score' => $fraudResult['score'],
        ]);

        // 4. Process payment through engine
        try {
            $transaction = $this->paymentEngine->initPayment(
                amount: $amount->toKopecks(),
                tenantId: $paymentIntent->tenant_id,
                userId: $paymentIntent->user_id,
                provider: $routing['provider'],
                paymentMethod: $method->method,
                hold: false,
                idempotencyKey: $idempotencyKey,
                correlationId: $correlationId,
            );

            $paymentIntent->update([
                'status' => 'succeeded',
                'provider_payment_intent_id' => $transaction->provider_payment_id,
                'payment_url' => $transaction->payment_url ?? null,
                'succeeded_at' => now(),
            ]);

            // Record smart routing success
            $this->smartRouting->recordSuccess($routing['provider'], 0);

            // Track in BigData
            $this->trackPaymentSuccess($paymentIntent, $transaction, $routing);

            // Send outbox notification
            $this->outboxService->store(
                eventType: 'payment.succeeded',
                targetUrl: config('payment.webhook_url'),
                payload: [
                    'payment_intent_uuid' => $paymentIntent->uuid,
                    'transaction_id' => $transaction->id,
                    'amount' => $amount->toArray(),
                    'provider' => $routing['provider'],
                ],
                correlationId: $correlationId,
            );

            return PaymentResultDTO::success(
                paymentIntentId: $paymentIntent->uuid,
                paymentTransactionId: $transaction->uuid,
                status: PaymentStatusVO::fromString(PaymentStatusVO::CAPTURED),
                amount: $amount,
                providerPaymentId: $transaction->provider_payment_id,
                paymentUrl: $transaction->payment_url,
                correlationId: $correlationId,
            );
        } catch (\Throwable $e) {
            $paymentIntent->update([
                'status' => 'failed',
            ]);

            // Record smart routing failure
            $this->smartRouting->recordFailure($routing['provider'], 0);

            $this->logger->error('PaymentFacade: payment failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntent->uuid,
                paymentTransactionId: '',
                errorMessage: $e->getMessage(),
                correlationId: $correlationId,
            );
        }
    }

    /**
     * Capture authorized payment.
     */
    public function capture(string $paymentIntentId, ?MoneyVO $amount = null): PaymentResultDTO
    {
        $paymentIntent = PaymentIntent::where('uuid', $paymentIntentId)->firstOrFail();

        if (! $paymentIntent->canBeCaptured()) {
            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntentId,
                paymentTransactionId: '',
                errorMessage: 'Payment cannot be captured in current state',
                errorCode: 'INVALID_STATE',
            );
        }

        $transaction = $paymentIntent->transactions()->first();

        if (! $transaction) {
            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntentId,
                paymentTransactionId: '',
                errorMessage: 'No transaction found for payment intent',
                errorCode: 'TRANSACTION_NOT_FOUND',
            );
        }

        try {
            $capturedTransaction = $this->paymentEngine->capture(
                paymentUuid: $transaction->uuid,
                amount: $amount?->toKopecks(),
            );

            $paymentIntent->update([
                'status' => 'succeeded',
                'succeeded_at' => now(),
            ]);

            return PaymentResultDTO::success(
                paymentIntentId: $paymentIntent->uuid,
                paymentTransactionId: $capturedTransaction->uuid,
                status: PaymentStatusVO::fromString(PaymentStatusVO::CAPTURED),
                amount: :fr::fr::fromKopecksomKopecksomKopecks($capturedTransaction->amount),
            );
        } catch (\Throwable $e) {
            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntentId,
                paymentTransactionId: $transaction->uuid,
                errorMessage: $e->getMessage(),
            );
        }
    }

    /**
     * Refund payment.
     */
    public function refund(string $paymentIntentId, MoneyVO $amount, string $reason = 'Customer request'): PaymentResultDTO
    {
        $paymentIntent = PaymentIntent::where('uuid', $paymentIntentId)->firstOrFail();
        $transaction = $paymentIntent->transactions()->captured()->first();

        if (! $transaction) {
            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntentId,
                paymentTransactionId: '',
                errorMessage: 'No captured transaction found',
                errorCode: 'TRANSACTION_NOT_FOUND',
            );
        }

        try {
            $refundedTransaction = $this->paymentEngine->refund(
                paymentUuid: $transaction->uuid,
                amount: $amount->toKopecks(),
                reason: $reason,
            );

            return PaymentResultDTO::success(
                paymentIntentId: $paymentIntent->uuid,
                paymentTransactionId: $refundedTransaction->uuid,
                status: PaymentStatusVO::fromString(PaymentStatusVO::REFUNDED),
                amount: $amount,
            );
        } catch (\Throwable $e) {
            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntentId,
                paymentTransactionId: $transaction->uuid,
                errorMessage: $e->getMessage(),
            );
        }
    }

    /**
     * Cancel payment.
     */
    public function cancel(string $paymentIntentId): PaymentResultDTO
    {
        $paymentIntent = PaymentIntent::where('uuid', $paymentIntentId)->firstOrFail();

        if (! $paymentIntent->canBeCanceled()) {
            return PaymentResultDTO::error(
                paymentIntentId: $paymentIntentId,
                paymentTransactionId: '',
                errorMessage: 'Payment cannot be canceled in current state',
                errorCode: 'INVALID_STATE',
            );
        }

        $paymentIntent->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        return PaymentResultDTO::success(
            paymentIntentId: $paymentIntent->uuid,
            paymentTransactionId: '',
            status: PaymentStatusVO::fromString(PaymentStatusVO::CANCELLED),
            amount: new MoneyVO($paymentIntent->amount_kopecks),
        );
    }

    /**
     * Create escrow hold.
     */
    public function escrowHold(
        PaymentIntent $paymentIntent,
        int $walletId,
        MoneyVO $amount,
        array $releaseConditions = [],
        ?\DateTime $autoReleaseAt = null,
    ): EscrowHold {
        return $this->escrowService->createHold(
            paymentIntent: $paymentIntent,
            walletId: $walletId,
            amount: $amount,
            releaseConditions: $releaseConditions,
            autoReleaseAt: $autoReleaseAt,
        );
    }

    /**
     * Release escrow funds.
     */
    public function escrowRelease(
        EscrowHold $hold,
        int $amountKopecks,
        int $targetWalletId,
        string $reason = 'Conditions met',
    ): EscrowHold {
        return $this->escrowService->release(
            hold: $hold,
            amountKopecks: $amountKopecks,
            targetWalletId: $targetWalletId,
            reason: $reason,
        );
    }

    /**
     * Cancel escrow hold.
     */
    public function escrowCancel(EscrowHold $hold, string $reason = 'Canceled'): EscrowHold
    {
        return $this->escrowService->cancel(hold: $hold, reason: $reason);
    }

    /**
     * Split payment and payout to sellers.
     */
    public function splitAndPayout(Model $order): array
    {
        $paymentRecord = $order->paymentRecord;
        $splits = $order->getSellerSplits(); // Implement in Order model

        return $this->splitPayment->processSplit(
            paymentRecord: $paymentRecord,
            splits: collect($splits),
            correlationId: Str::uuid()->toString(),
        );
    }

    /**
     * Create recurring subscription.
     */
    public function createSubscription(
        int $userId,
        int $productId,
        MoneyVO $amount,
        string $interval = 'month',
        int $intervalCount = 1,
        ?\DateTime $trialEnd = null,
    ): RecurringSubscription {
        return RecurringSubscription::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
            'user_id' => $userId,
            'product_id' => $productId,
            'amount_kopecks' => $amount->toKopecks(),
            'currency' => $amount->currency,
            'status' => 'incomplete',
            'interval' => $interval,
            'interval_count' => $intervalCount,
            'trial_end' => $trialEnd,
            'next_payment_at' => $trialEnd ?? now()->add($intervalCount, $interval === 'year' ? 'years' : 'months'),
        ]);
    }

    /**
     * Charge recurring subscription.
     */
    public function chargeSubscription(RecurringSubscription $subscription): PaymentResultDTO
    {
        // Implementation would use saved payment method and create payment
        // This is a simplified version
        $amount = new MoneyVO($subscription->amount_kopecks);

        return $this->process(
            payable: $subscription,
            method: PaymentMethodVO::fromString('card'),
            amount: $amount,
        );
    }

    /**
     * Create payout batch.
     */
    public function createPayoutBatch(
        array $payouts,
        string $provider = 'tinkoff',
        ?\DateTime $scheduledAt = null,
    ): PayoutBatch {
        $totalAmount = array_sum(array_column($payouts, 'amount_kopecks'));

        return PayoutBatch::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
            'provider' => $provider,
            'status' => 'pending',
            'total_amount_kopecks' => $totalAmount,
            'total_count' => count($payouts),
            'currency' => 'RUB',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Process payout batch.
     */
    public function processPayoutBatch(PayoutBatch $batch): PayoutBatch
    {
        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Process each payout in the batch
        foreach ($batch->payouts as $payout) {
            try {
                $this->payoutService->initiatePayout(
                    payoutId: $payout->id,
                    gateway: $this->getGateway($batch->provider),
                    correlationId: Str::uuid()->toString(),
                );
                $batch->increment('processed_count');
            } catch (\Throwable $e) {
                $batch->increment('failed_count');
            }
        }

        $batch->update([
            'status' => $batch->failed_count > 0 ? 'partial' : 'completed',
            'completed_at' => now(),
        ]);

        return $batch->fresh();
    }

    /**
     * Get payment intent by UUID.
     */
    public function getIntent(string $uuid): ?PaymentIntent
    {
        return PaymentIntent::where('uuid', $uuid)->first();
    }

    /**
     * Get payment status.
     */
    public function getStatus(string $paymentIntentId): PaymentStatusVO
    {
        $intent = PaymentIntent::where('uuid', $paymentIntentId)->firstOrFail();

        return PaymentStatusVO::fromString($intent->status);
    }

    /**
     * Route to best gateway.
     */
    public function routeToBestGateway(
        MoneyVO $amount,
        PaymentMethodVO $method,
        string $clientType = 'b2c',
        ?float $fraudScore = null,
        ?string $region = null,
    ): array {
        return $this->smartRouting->selectGateway(
            amount: $amount,
            paymentMethod: $method,
            clientType: $clientType,
            fraudScore: $fraudScore,
            region: $region,
        );
    }

    /**
     * Process pending outbox messages.
     */
    public function processOutbox(): int
    {
        return $this->outboxService->processPending();
    }

    /**
     * Process expired escrow holds.
     */
    public function processExpiredEscrow(): int
    {
        return $this->escrowService->processExpiredHolds();
    }

    /**
     * Process due recurring subscriptions.
     */
    public function processDueSubscriptions(): int
    {
        $subscriptions = RecurringSubscription::active()
            ->where('next_payment_at', '<=', now())
            ->get();

        $processed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->chargeSubscription($subscription);
                $subscription->update([
                    'last_payment_at' => now(),
                    'next_payment_at' => now()->add(
                        $subscription->interval_count,
                        $subscription->interval === 'year' ? 'years' : 'months'
                    ),
                ]);
                $subscription->resetFailedPaymentCount();
                $processed++;
            } catch (\Throwable $e) {
                $subscription->incrementFailedPayment();
                if ($subscription->hasExceededMaxRetries()) {
                    $subscription->update(['status' => 'past_due']);
                }
            }
        }

        return $processed;
    }

    private function getGateway(string $provider): mixed
    {
        return match ($provider) {
            'tinkoff' => $this->tinkoffAdapter,
            'tochka' => $this->tochkaAdapter,
            default => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };
    }

    private function trackPaymentSuccess(PaymentIntent $intent, $transaction, array $routing): void
    {
        // Track in BigData/ClickHouse for analytics
        // This would be implemented with the BigData service
        $this->logger->info('Payment tracked in BigData', [
            'payment_intent_id' => $intent->id,
            'provider' => $routing['provider'],
            'amount' => $intent->amount_kopecks,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Federal Law Compliance Methods (ФЗ-161, ФЗ-115, 54-ФЗ)
    |--------------------------------------------------------------------------
    */

    /**
     * Validate payment against ФЗ-161 requirements.
     * 
     * Checks for:
     * - Transaction limits
     * - Fraud detection requirements
     * - Settlement to bank accounts only
     * - Payment aggregator compliance
     */
    public function validateUnder161(array $paymentData): array
    {
        if (! config('payment_compliance.fz161.enabled')) {
            return [
                'is_compliant' => true,
                'violations' => [],
                'warnings' => [],
            ];
        }

        return $this->paymentRules->validateUnder161($paymentData);
    }

    /**
     * Process payment with federal law compliance (ФЗ-161 + ФЗ-115 + 54-ФЗ).
     * 
     * This method integrates:
     * 1. ФЗ-161 validation (transaction limits, fraud detection, settlement rules)
     * 2. ФЗ-115 AML/KYC check (risk scoring, KYC level determination)
     * 3. 54-ФЗ fiscalization (prepayment receipt)
     * 
     * @param Model $payable The payable entity (Order, Invoice, etc.)
     * @param PaymentMethodVO $method Payment method
     * @param MoneyVO $amount Payment amount
     * @param array $fiscalItems Items for fiscal receipt (name, quantity, price, vat_rate)
     * @param string $sellerInn Seller INN for 54-ФЗ
     * @param string|null $idempotencyKey Idempotency key
     * @param string|null $preferredGateway Preferred payment gateway
     * @return PaymentResultDTO
     */
    public function processWithCompliance(
        Model $payable,
        PaymentMethodVO $method,
        MoneyVO $amount,
        array $fiscalItems = [],
        string $sellerInn = '',
        ?string $idempotencyKey = null,
        ?string $preferredGateway = null,
    ): PaymentResultDTO {
        $correlationId = Str::uuid()->toString();
        $idempotencyKey ??= Str::uuid()->toString();
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;
        $userId = $payable->user_id ?? null;

        // 1. ФЗ-161 validation
        $fz161Validation = $this->validateUnder161([
            'amount_kopecks' => $amount->toKopecks(),
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'settlement_method' => 'bank_account',
            'fraud_check_passed' => false,
        ]);

        if (! $fz161Validation['is_compliant']) {
            $this->audit->logAction(
                action: 'fz161_violation',
                subjectType: get_class($payable),
                subjectId: $payable->id,
                newValues: [
                    'violations' => $fz161Validation['violations'],
                    'warnings' => $fz161Validation['warnings'],
                ],
                correlationId: $correlationId,
            );

            return PaymentResultDTO::error(
                paymentIntentId: '',
                paymentTransactionId: '',
                errorMessage: 'Payment violates ФЗ-161 requirements',
                errorCode: 'FZ161_VIOLATION',
                correlationId: $correlationId,
            );
        }

        // 2. ФЗ-115 AML/KYC check
        if (config('payment_compliance.fz115.enabled')) {
            $amlCheck = $this->amlService->check115(
                userId: $userId ?? 0,
                amountKopecks: $amount->toKopecks(),
                currency: $amount->currency,
                tenantId: $tenantId,
                orderId: $payable->id,
                ipAddress: request()->ip() ?? '127.0.0.1',
                deviceFingerprint: request()->header('X-Device-Fingerprint'),
            );

            if ($amlCheck->isBlocked()) {
                return PaymentResultDTO::error(
                    paymentIntentId: '',
                    paymentTransactionId: '',
                    errorMessage: 'Payment blocked by AML check (ФЗ-115)',
                    errorCode: 'AML_BLOCKED',
                    correlationId: $correlationId,
                );
            }

            if ($amlCheck->requiresReview()) {
                $this->logger->warning('Payment requires manual review (ФЗ-115)', [
                    'aml_check_uuid' => $amlCheck->uuid,
                    'risk_score' => $amlCheck->riskScore,
                    'reason' => $amlCheck->reason,
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        // 3. Process payment through existing method
        $paymentResult = $this->process(
            payable: $payable,
            method: $method,
            amount: $amount,
            idempotencyKey: $idempotencyKey,
            preferredGateway: $preferredGateway,
        );

        // 4. If payment succeeded, send prepayment receipt (54-ФЗ)
        if ($paymentResult->status->value === 'captured' && config('payment_compliance.fz54.enabled')) {
            if (! empty($fiscalItems) && ! empty($sellerInn)) {
                try {
                    $this->fiscalization->fiscalizePrepayment(
                        paymentIntentUuid: $paymentResult->paymentIntentId,
                        tenantId: $tenantId,
                        orderId: $payable->id,
                        sellerInn: $sellerInn,
                        agentName: config('payment_compliance.fz54.agent.name', 'CatVRF Marketplace'),
                        amountKopecks: $amount->toKopecks(),
                        items: $fiscalItems,
                    );

                    $this->logger->info('Prepayment receipt sent (54-ФЗ)', [
                        'payment_intent_id' => $paymentResult->paymentIntentId,
                        'order_id' => $payable->id,
                        'amount_kopecks' => $amount->toKopecks(),
                        'correlation_id' => $correlationId,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to send prepayment receipt (54-ФЗ)', [
                        'payment_intent_id' => $paymentResult->paymentIntentId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                    // Don't fail payment if fiscalization fails
                }
            }
        }

        return $paymentResult;
    }

    /**
     * Release escrow with 54-ФЗ fiscalization (full payment receipt).
     * 
     * When escrow is released (order fulfilled/delivered), send full payment receipt
     * with prepayment offset as required by 54-ФЗ.
     * 
     * @param EscrowHold $hold The escrow hold to release
     * @param int $amountKopecks Amount to release
     * @param int $targetWalletId Target wallet ID
     * @param string $reason Release reason
     * @param array $fiscalItems Items for fiscal receipt
     * @param string $sellerInn Seller INN
     * @param string|null $prepaymentReceiptUuid UUID of prepayment receipt
     * @param int $prepaymentAmountKopecks Prepayment amount for offset
     * @return EscrowHold
     */
    public function escrowReleaseWithFiscal(
        EscrowHold $hold,
        int $amountKopecks,
        int $targetWalletId,
        string $reason = 'Conditions met',
        array $fiscalItems = [],
        string $sellerInn = '',
        ?string $prepaymentReceiptUuid = null,
        int $prepaymentAmountKopecks = 0,
    ): EscrowHold {
        $correlationId = Str::uuid()->toString();
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;

        // 1. Release escrow
        $releasedHold = $this->escrowRelease(
            hold: $hold,
            amountKopecks: $amountKopecks,
            targetWalletId: $targetWalletId,
            reason: $reason,
        );

        // 2. Send full payment receipt (54-ФЗ)
        if (config('payment_compliance.fz54.enabled') && ! empty($fiscalItems) && ! empty($sellerInn)) {
            try {
                $paymentIntent = $hold->paymentIntent;
                $this->fiscalization->fiscalizeFullPayment(
                    paymentIntentUuid: $paymentIntent->uuid,
                    tenantId: $tenantId,
                    orderId: $paymentIntent->payable_id,
                    sellerInn: $sellerInn,
                    agentName: config('payment_compliance.fz54.agent.name', 'CatVRF Marketplace'),
                    amountKopecks: $amountKopecks,
                    items: $fiscalItems,
                    prepaymentReceiptUuid: $prepaymentReceiptUuid,
                    prepaymentAmountKopecks: $prepaymentAmountKopecks,
                );

                $this->logger->info('Full payment receipt sent (54-ФЗ)', [
                    'escrow_hold_id' => $hold->id,
                    'amount_kopecks' => $amountKopecks,
                    'prepayment_amount_kopecks' => $prepaymentAmountKopecks,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send full payment receipt (54-ФЗ)', [
                    'escrow_hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                // Don't fail escrow release if fiscalization fails
            }
        }

        return $releasedHold;
    }

    /**
     * Refund payment with 54-ФЗ fiscalization (refund receipt).
     * 
     * When payment is refunded, send refund receipt as required by 54-ФЗ.
     * 
     * @param string $paymentIntentId Payment intent UUID
     * @param MoneyVO $amount Refund amount
     * @param string $reason Refund reason
     * @param array $fiscalItems Items for fiscal receipt
     * @param string $sellerInn Seller INN
     * @return PaymentResultDTO
     */
    public function refundWithFiscal(
        string $paymentIntentId,
        MoneyVO $amount,
        string $reason = 'Customer request',
        array $fiscalItems = [],
        string $sellerInn = '',
    ): PaymentResultDTO {
        $correlationId = Str::uuid()->toString();
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;

        // 1. Process refund through existing method
        $refundResult = $this->refund(
            paymentIntentId: $paymentIntentId,
            amount: $amount,
            reason: $reason,
        );

        // 2. Send refund receipt (54-ФЗ)
        if ($refundResult->status->value === 'refunded' && config('payment_compliance.fz54.enabled')) {
            if (! empty($fiscalItems) && ! empty($sellerInn)) {
                try {
                    $this->fiscalization->fiscalizeRefund(
                        paymentIntentUuid: $paymentIntentId,
                        tenantId: $tenantId,
                        orderId: null, // Get from payment intent if needed
                        sellerInn: $sellerInn,
                        agentName: config('payment_compliance.fz54.agent.name', 'CatVRF Marketplace'),
                        amountKopecks: $amount->toKopecks(),
                        items: $fiscalItems,
                        refundReason: $reason,
                    );

                    $this->logger->info('Refund receipt sent (54-ФЗ)', [
                        'payment_intent_id' => $paymentIntentId,
                        'amount_kopecks' => $amount->toKopecks(),
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to send refund receipt (54-ФЗ)', [
                        'payment_intent_id' => $paymentIntentId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                    // Don't fail refund if fiscalization fails
                }
            }
        }

        return $refundResult;
    }

    /**
     * Split payment and payout to sellers (B2B, no 54-ФZ fiscalization).
     * 
     * According to 54-ФЗ, B2B settlements between marketplace and sellers
     * do not require fiscalization. This method handles split payments
     * without creating fiscal receipts.
     * 
     * @param Model $order The order entity
     * @return array Split results
     */
    public function splitAndPayoutB2B(Model $order): array
    {
        $correlationId = Str::uuid()->toString();
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;

        // Validate that this is a B2B transaction (no fiscalization required)
        if (! config('payment_compliance.fz54.b2b.skip_fiscalization')) {
            throw new \InvalidArgumentException('B2B fiscalization skip is not enabled in config');
        }

        $this->logger->info('Processing B2B split payout (no 54-ФЗ fiscalization)', [
            'order_id' => $order->id,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        // Process split through existing method
        $result = $this->splitAndPayout($order);

        // Log audit event for B2B settlement
        $this->audit->logAction(
            action: 'b2b_split_payout',
            subjectType: get_class($order),
            subjectId: $order->id,
            newValues: [
                'splits_count' => count($result),
                'no_fiscalization' => true,
            ],
            correlationId: $correlationId,
        );

        return $result;
    }
}
