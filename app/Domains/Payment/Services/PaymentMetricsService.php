<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use Illuminate\Support\Facades\Cache;
use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Prometheus\Counter;
use Prometheus\Gauge;

/**
 * PaymentMetricsService - Prometheus metrics for payment layer.
 *
 * CRITICAL: Provides observability for payment operations.
 * Metrics exported for log-based scraping (no direct HTTP endpoint).
 *
 * Metrics tracked:
 * - Payment success rate
 * - Payment latency
 * - Fraud block rate
 * - Gateway success rate per provider
 * - Wallet volume
 * - Payment attempts per tenant
 *
 * @package App\Domains\Payment\Services
 */
final readonly class PaymentMetricsService
{
    private const NAMESPACE = 'catvrf_payment';

    private Histogram $paymentLatency;
    private Counter $paymentSuccess;
    private Counter $paymentFailed;
    private Counter $paymentFraudBlocked;
    private Counter $gatewaySuccess;
    private Counter $gatewayFailed;
    private Gauge $walletBalance;
    private Counter $paymentAttempts;

    public function __construct(
        private CollectorRegistry $registry,
    ) {
        $this->paymentLatency = $this->registry->getOrRegisterHistogram(
            self::NAMESPACE,
            'payment_latency_seconds',
            'Payment processing latency in seconds',
            ['vertical_code', 'provider'],
            [0.1, 0.5, 1.0, 2.0, 5.0, 10.0],
        );

        $this->paymentSuccess = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            'payment_success_total',
            'Total successful payments',
            ['vertical_code', 'provider', 'tenant_id'],
        );

        $this->paymentFailed = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            'payment_failed_total',
            'Total failed payments',
            ['vertical_code', 'provider', 'tenant_id', 'error_type'],
        );

        $this->paymentFraudBlocked = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            'payment_fraud_blocked_total',
            'Total payments blocked by fraud detection',
            ['vertical_code', 'tenant_id', 'fraud_type'],
        );

        $this->gatewaySuccess = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            'gateway_success_total',
            'Total successful gateway calls',
            ['provider'],
        );

        $this->gatewayFailed = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            'gateway_failed_total',
            'Total failed gateway calls',
            ['provider', 'error_type'],
        );

        $this->walletBalance = $this->registry->getOrRegisterGauge(
            self::NAMESPACE,
            'wallet_balance_kopecks',
            'Current wallet balance in kopecks',
            ['tenant_id', 'wallet_id'],
        );

        $this->paymentAttempts = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            'payment_attempts_total',
            'Total payment attempts',
            ['tenant_id', 'vertical_code'],
        );
    }

    /**
     * Record payment latency.
     */
    public function recordPaymentLatency(float $seconds, string $verticalCode, string $provider): void
    {
        $this->paymentLatency->observe($seconds, [$verticalCode, $provider]);
    }

    /**
     * Record successful payment.
     */
    public function recordPaymentSuccess(string $verticalCode, string $provider, int $tenantId): void
    {
        $this->paymentSuccess->inc([$verticalCode, $provider, (string) $tenantId]);
    }

    /**
     * Record failed payment.
     */
    public function recordPaymentFailed(string $verticalCode, string $provider, int $tenantId, string $errorType): void
    {
        $this->paymentFailed->inc([$verticalCode, $provider, (string) $tenantId, $errorType]);
    }

    /**
     * Record fraud-blocked payment.
     */
    public function recordPaymentFraudBlocked(string $verticalCode, int $tenantId, string $fraudType): void
    {
        $this->paymentFraudBlocked->inc([$verticalCode, (string) $tenantId, $fraudType]);
    }

    /**
     * Record successful gateway call.
     */
    public function recordGatewaySuccess(string $provider): void
    {
        $this->gatewaySuccess->inc([$provider]);
    }

    /**
     * Record failed gateway call.
     */
    public function recordGatewayFailed(string $provider, string $errorType): void
    {
        $this->gatewayFailed->inc([$provider, $errorType]);
    }

    /**
     * Record wallet balance.
     */
    public function recordWalletBalance(int $tenantId, int $walletId, int $balanceKopecks): void
    {
        $this->walletBalance->set($balanceKopecks, [(string) $tenantId, (string) $walletId]);
    }

    /**
     * Record payment attempt.
     */
    public function recordPaymentAttempt(int $tenantId, string $verticalCode): void
    {
        $this->paymentAttempts->inc([(string) $tenantId, $verticalCode]);
    }

    /**
     * Get metrics for log-based scraping.
     *
     * @return array Metrics data
     */
    public function getMetricsForLog(): array
    {
        $metrics = [];

        foreach ($this->registry->getMetricFamilySamples() as $sample) {
            $metrics[] = [
                'name' => $sample->getName(),
                'type' => $sample->getType(),
                'help' => method_exists($sample, 'getHelp') ? $sample->getHelp() : '',
                'samples' => array_map(function ($s) {
                    return [
                        'labels' => method_exists($s, 'getLabels') ? $s->getLabels() : [],
                        'value' => $s->getValue(),
                    ];
                }, $sample->getSamples()),
            ];
        }

        return $metrics;
    }

    /**
     * Log metrics for external scraping (e.g., by log collector).
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function logMetrics(\Psr\Log\LoggerInterface $logger): void
    {
        $logger->info('Payment metrics', $this->getMetricsForLog());
    }
}
