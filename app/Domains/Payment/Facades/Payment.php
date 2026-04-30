<?php

declare(strict_types=1);

namespace App\Domains\Payment\Facades;

use App\Domains\Payment\DTOs\PaymentIntentDTO;
use App\Domains\Payment\DTOs\PaymentResultDTO;
use App\Domains\Payment\Models\EscrowHold;
use App\Domains\Payment\Models\PaymentIntent;
use App\Domains\Payment\Models\PayoutBatch;
use App\Domains\Payment\Models\RecurringSubscription;
use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;
use App\Domains\Payment\ValueObjects\PaymentStatusVO;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment Facade - single entry point for all payment operations.
 *
 * Provides fluent API for:
 * - Payment processing (process, capture, refund)
 * - Escrow operations (hold, release, cancel)
 * - Recurring billing (create subscription, charge)
 * - Payouts (split, batch, instant)
 * - Smart routing (auto gateway selection)
 *
 * Usage:
 * Payment::process($order, $method)
 * Payment::refund($transactionId, $amount)
 * Payment::splitAndPayout($order)
 * Payment::escrowRelease($order)
 * Payment::routeToBestGateway($order)
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domains\Payment\Services\PaymentFacadeService::class;
    }

    /**
     * Process a payment (create intent and charge).
     *
     * @return PaymentResultDTO
     */
    public static function process(
        Model $payable,
        PaymentMethodVO $method,
        MoneyVO $amount,
        ?string $idempotencyKey = null,
        ?string $preferredGateway = null,
    ): PaymentResultDTO {
        return static::getFacadeRoot()->process(
            payable: $payable,
            method: $method,
            amount: $amount,
            idempotencyKey: $idempotencyKey,
            preferredGateway: $preferredGateway,
        );
    }

    /**
     * Capture a previously authorized payment.
     *
     * @return PaymentResultDTO
     */
    public static function capture(
        string $paymentIntentId,
        ?MoneyVO $amount = null,
    ): PaymentResultDTO {
        return static::getFacadeRoot()->capture(
            paymentIntentId: $paymentIntentId,
            amount: $amount,
        );
    }

    /**
     * Refund a payment (full or partial).
     *
     * @return PaymentResultDTO
     */
    public static function refund(
        string $paymentIntentId,
        MoneyVO $amount,
        string $reason = 'Customer request',
    ): PaymentResultDTO {
        return static::getFacadeRoot()->refund(
            paymentIntentId: $paymentIntentId,
            amount: $amount,
            reason: $reason,
        );
    }

    /**
     * Cancel a payment (before capture).
     *
     * @return PaymentResultDTO
     */
    public static function cancel(string $paymentIntentId): PaymentResultDTO
    {
        return static::getFacadeRoot()->cancel(paymentIntentId: $paymentIntentId);
    }

    /**
     * Create escrow hold for marketplace payment.
     */
    public static function escrowHold(
        PaymentIntent $paymentIntent,
        int $walletId,
        MoneyVO $amount,
        array $releaseConditions = [],
        ?\DateTime $autoReleaseAt = null,
    ): EscrowHold {
        return static::getFacadeRoot()->escrowHold(
            paymentIntent: $paymentIntent,
            walletId: $walletId,
            amount: $amount,
            releaseConditions: $releaseConditions,
            autoReleaseAt: $autoReleaseAt,
        );
    }

    /**
     * Release funds from escrow.
     */
    public static function escrowRelease(
        EscrowHold $hold,
        int $amountKopecks,
        int $targetWalletId,
        string $reason = 'Conditions met',
    ): EscrowHold {
        return static::getFacadeRoot()->escrowRelease(
            hold: $hold,
            amountKopecks: $amountKopecks,
            targetWalletId: $targetWalletId,
            reason: $reason,
        );
    }

    /**
     * Cancel escrow hold (return funds).
     */
    public static function escrowCancel(
        EscrowHold $hold,
        string $reason = 'Canceled',
    ): EscrowHold {
        return static::getFacadeRoot()->escrowCancel(
            hold: $hold,
            reason: $reason,
        );
    }

    /**
     * Split payment and payout to sellers.
     *
     * @return array{platform_fee: int, payouts: \Illuminate\Database\Eloquent\Collection}
     */
    public static function splitAndPayout(Model $order): array
    {
        return static::getFacadeRoot()->splitAndPayout(order: $order);
    }

    /**
     * Create recurring subscription.
     */
    public static function createSubscription(
        int $userId,
        int $productId,
        MoneyVO $amount,
        string $interval = 'month',
        int $intervalCount = 1,
        ?\DateTime $trialEnd = null,
    ): RecurringSubscription {
        return static::getFacadeRoot()->createSubscription(
            userId: $userId,
            productId: $productId,
            amount: $amount,
            interval: $interval,
            intervalCount: $intervalCount,
            trialEnd: $trialEnd,
        );
    }

    /**
     * Charge recurring subscription.
     *
     * @return PaymentResultDTO
     */
    public static function chargeSubscription(RecurringSubscription $subscription): PaymentResultDTO
    {
        return static::getFacadeRoot()->chargeSubscription(subscription: $subscription);
    }

    /**
     * Create payout batch for mass seller payouts.
     */
    public static function createPayoutBatch(
        array $payouts,
        string $provider = 'tinkoff',
        ?\DateTime $scheduledAt = null,
    ): PayoutBatch {
        return static::getFacadeRoot()->createPayoutBatch(
            payouts: $payouts,
            provider: $provider,
            scheduledAt: $scheduledAt,
        );
    }

    /**
     * Process payout batch.
     */
    public static function processPayoutBatch(PayoutBatch $batch): PayoutBatch
    {
        return static::getFacadeRoot()->processPayoutBatch(batch: $batch);
    }

    /**
     * Get payment intent by UUID.
     */
    public static function getIntent(string $uuid): ?PaymentIntent
    {
        return static::getFacadeRoot()->getIntent(uuid: $uuid);
    }

    /**
     * Get payment status.
     */
    public static function getStatus(string $paymentIntentId): PaymentStatusVO
    {
        return static::getFacadeRoot()->getStatus(paymentIntentId: $paymentIntentId);
    }

    /**
     * Route to best gateway based on smart routing.
     *
     * @return array{provider: string, confidence: float, reason: string}
     */
    public static function routeToBestGateway(
        MoneyVO $amount,
        PaymentMethodVO $method,
        string $clientType = 'b2c',
        ?float $fraudScore = null,
        ?string $region = null,
    ): array {
        return static::getFacadeRoot()->routeToBestGateway(
            amount: $amount,
            method: $method,
            clientType: $clientType,
            fraudScore: $fraudScore,
            region: $region,
        );
    }

    /**
     * Process pending outbox messages.
     */
    public static function processOutbox(): int
    {
        return static::getFacadeRoot()->processOutbox();
    }

    /**
     * Process expired escrow holds.
     */
    public static function processExpiredEscrow(): int
    {
        return static::getFacadeRoot()->processExpiredEscrow();
    }

    /**
     * Process due recurring subscriptions.
     */
    public static function processDueSubscriptions(): int
    {
        return static::getFacadeRoot()->processDueSubscriptions();
    }
}
