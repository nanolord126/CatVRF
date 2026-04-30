<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Prometheus\Counter;
use Prometheus\Gauge;

/**
 * Prometheus metrics collector for Supermarket vertical.
 * 
 * Tracks key metrics for monitoring and alerting:
 * - Order processing times
 * - Order counts by status
 * - Subscription metrics
 * - Return processing metrics
 * - External API call durations
 * - Inventory reservation success rates
 */
final class SupermarketMetrics
{
    private CollectorRegistry $registry;

    private Histogram $orderProcessingDuration;
    private Histogram $subscriptionProcessingDuration;
    private Histogram $returnProcessingDuration;
    private Histogram $externalApiCallDuration;

    private Counter $ordersCreated;
    private Counter $ordersCompleted;
    private Counter $ordersCancelled;
    private Counter $ordersFailed;

    private Counter $subscriptionsCreated;
    private Counter $subscriptionsPaused;
    private Counter $subscriptionsCancelled;
    private Counter $subscriptionPaymentsFailed;

    private Counter $returnsCreated;
    private Counter $returnsApproved;
    private Counter $returnsRejected;
    private Counter $returnsCompleted;

    private Gauge $activeSubscriptions;
    private Gauge $pendingReturns;
    private Gauge failedHonestyMarkValidations;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        // Histograms for duration tracking
        $this->orderProcessingDuration = $this->registry->getOrRegisterHistogram(
            'supermarket_order_processing_duration_seconds',
            'Order processing duration in seconds',
            ['sub_vertical', 'status'],
            [0.1, 0.5, 1, 2, 5, 10, 30, 60]
        );

        $this->subscriptionProcessingDuration = $this->registry->getOrRegisterHistogram(
            'supermarket_subscription_processing_duration_seconds',
            'Subscription processing duration in seconds',
            ['operation'],
            [0.1, 0.5, 1, 2, 5, 10, 30]
        );

        $this->returnProcessingDuration = $this->registry->getOrRegisterHistogram(
            'supermarket_return_processing_duration_seconds',
            'Return processing duration in seconds',
            ['status'],
            [0.5, 1, 2, 5, 10, 30, 60]
        );

        $this->externalApiCallDuration = $this->registry->getOrRegisterHistogram(
            'supermarket_external_api_call_duration_seconds',
            'External API call duration in seconds',
            ['api_name', 'operation', 'status'],
            [0.1, 0.5, 1, 2, 5, 10, 30]
        );

        // Counters for event tracking
        $this->ordersCreated = $this->registry->getOrRegisterCounter(
            'supermarket_orders_created_total',
            'Total number of orders created',
            ['sub_vertical', 'is_b2b']
        );

        $this->ordersCompleted = $this->registry->getOrRegisterCounter(
            'supermarket_orders_completed_total',
            'Total number of orders completed',
            ['sub_vertical']
        );

        $this->ordersCancelled = $this->registry->getOrRegisterCounter(
            'supermarket_orders_cancelled_total',
            'Total number of orders cancelled',
            ['reason']
        );

        $this->ordersFailed = $this->registry->getOrRegisterCounter(
            'supermarket_orders_failed_total',
            'Total number of orders that failed',
            ['failure_reason']
        );

        $this->subscriptionsCreated = $this->registry->getOrRegisterCounter(
            'supermarket_subscriptions_created_total',
            'Total number of subscriptions created',
            ['frequency']
        );

        $this->subscriptionsPaused = $this->registry->getOrRegisterCounter(
            'supermarket_subscriptions_paused_total',
            'Total number of subscriptions paused',
            ['reason']
        );

        $this->subscriptionsCancelled = $this->registry->getOrRegisterCounter(
            'supermarket_subscriptions_cancelled_total',
            'Total number of subscriptions cancelled',
            ['reason']
        );

        $this->subscriptionPaymentsFailed = $this->registry->getOrRegisterCounter(
            'supermarket_subscription_payments_failed_total',
            'Total number of subscription payment failures',
            ['attempt_number']
        );

        $this->returnsCreated = $this->registry->getOrRegisterCounter(
            'supermarket_returns_created_total',
            'Total number of returns created',
            ['reason_type', 'is_cold_chain']
        );

        $this->returnsApproved = $this->registry->getOrRegisterCounter(
            'supermarket_returns_approved_total',
            'Total number of returns approved',
            []
        );

        $this->returnsRejected = $this->registry->getOrRegisterCounter(
            'supermarket_returns_rejected_total',
            'Total number of returns rejected',
            ['rejection_reason']
        );

        $this->returnsCompleted = $this->registry->getOrRegisterCounter(
            'supermarket_returns_completed_total',
            'Total number of returns completed',
            []
        );

        // Gauges for current state
        $this->activeSubscriptions = $this->registry->getOrRegisterGauge(
            'supermarket_active_subscriptions',
            'Number of active subscriptions',
            ['seller_id']
        );

        $this->pendingReturns = $this->registry->getOrRegisterGauge(
            'supermarket_pending_returns',
            'Number of pending returns',
            ['seller_id']
        );

        $this->failedHonestyMarkValidations = $this->registry->getOrRegisterGauge(
            'supermarket_failed_honesty_mark_validations',
            'Number of failed honesty mark validations in last hour',
            []
        );
    }

    /**
     * Record order processing duration.
     */
    public function recordOrderProcessing(float $duration, string $subVertical, string $status): void
    {
        $this->orderProcessingDuration->observe($duration, [$subVertical, $status]);
    }

    /**
     * Record subscription processing duration.
     */
    public function recordSubscriptionProcessing(float $duration, string $operation): void
    {
        $this->subscriptionProcessingDuration->observe($duration, [$operation]);
    }

    /**
     * Record return processing duration.
     */
    public function recordReturnProcessing(float $duration, string $status): void
    {
        $this->returnProcessingDuration->observe($duration, [$status]);
    }

    /**
     * Record external API call duration.
     */
    public function recordExternalApiCall(float $duration, string $apiName, string $operation, string $status): void
    {
        $this->externalApiCallDuration->observe($duration, [$apiName, $operation, $status]);
    }

    /**
     * Increment orders created counter.
     */
    public function incrementOrdersCreated(string $subVertical, bool $isB2B = false): void
    {
        $this->ordersCreated->inc([$subVertical, $isB2B ? 'true' : 'false']);
    }

    /**
     * Increment orders completed counter.
     */
    public function incrementOrdersCompleted(string $subVertical): void
    {
        $this->ordersCompleted->inc([$subVertical]);
    }

    /**
     * Increment orders cancelled counter.
     */
    public function incrementOrdersCancelled(string $reason = 'customer'): void
    {
        $this->ordersCancelled->inc([$reason]);
    }

    /**
     * Increment orders failed counter.
     */
    public function incrementOrdersFailed(string $failureReason): void
    {
        $this->ordersFailed->inc([$failureReason]);
    }

    /**
     * Increment subscriptions created counter.
     */
    public function incrementSubscriptionsCreated(string $frequency): void
    {
        $this->subscriptionsCreated->inc([$frequency]);
    }

    /**
     * Increment subscriptions paused counter.
     */
    public function incrementSubscriptionsPaused(string $reason = 'payment_failed'): void
    {
        $this->subscriptionsPaused->inc([$reason]);
    }

    /**
     * Increment subscriptions cancelled counter.
     */
    public function incrementSubscriptionsCancelled(string $reason = 'customer'): void
    {
        $this->subscriptionsCancelled->inc([$reason]);
    }

    /**
     * Increment subscription payment failures counter.
     */
    public function incrementSubscriptionPaymentsFailed(int $attemptNumber): void
    {
        $this->subscriptionPaymentsFailed->inc([(string) $attemptNumber]);
    }

    /**
     * Increment returns created counter.
     */
    public function incrementReturnsCreated(string $reasonType, bool $isColdChain): void
    {
        $this->returnsCreated->inc([$reasonType, $isColdChain ? 'true' : 'false']);
    }

    /**
     * Increment returns approved counter.
     */
    public function incrementReturnsApproved(): void
    {
        $this->returnsApproved->inc();
    }

    /**
     * Increment returns rejected counter.
     */
    public function incrementReturnsRejected(string $rejectionReason): void
    {
        $this->returnsRejected->inc([$rejectionReason]);
    }

    /**
     * Increment returns completed counter.
     */
    public function incrementReturnsCompleted(): void
    {
        $this->returnsCompleted->inc();
    }

    /**
     * Set active subscriptions gauge.
     */
    public function setActiveSubscriptions(int $count, ?string $sellerId = null): void
    {
        $this->activeSubscriptions->set($count, [$sellerId ?? 'all']);
    }

    /**
     * Set pending returns gauge.
     */
    public function setPendingReturns(int $count, ?string $sellerId = null): void
    {
        $this->pendingReturns->set($count, [$sellerId ?? 'all']);
    }

    /**
     * Set failed honesty mark validations gauge.
     */
    public function setFailedHonestyMarkValidations(int $count): void
    {
        $this->failedHonestyMarkValidations->set($count, []);
    }
}
