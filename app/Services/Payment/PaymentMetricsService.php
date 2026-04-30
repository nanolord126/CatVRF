<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Payment Metrics Service
 *
 * Exports Prometheus metrics for payment operations.
 * Integrates with the existing Prometheus infrastructure.
 */
final readonly class PaymentMetricsService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Record successful payment
     */
    public function recordPaymentSuccess(
        string $provider,
        int $amount,
        string $currency,
        float $durationSeconds
    ): void {
        $this->logger->channel('prometheus')->$this->logger->info('payment_success', [
            'provider' => $provider,
            'amount' => $amount,
            'currency' => $currency,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    /**
     * Record failed payment
     */
    public function recordPaymentFailure(
        string $provider,
        string $reason,
        int $amount
    ): void {
        $this->logger->channel('prometheus')->$this->logger->info('payment_failure', [
            'provider' => $provider,
            'reason' => $reason,
            'amount' => $amount,
        ]);
    }

    /**
     * Record payment attempt
     */
    public function recordPaymentAttempt(string $provider): void
    {
        $this->logger->channel('prometheus')->$this->logger->info('payment_attempt', [
            'provider' => $provider,
        ]);
    }

    /**
     * Record wallet credit
     */
    public function recordWalletCredit(
        int $amount,
        string $type
    ): void {
        $this->logger->channel('prometheus')->$this->logger->info('wallet_credit', [
            'amount' => $amount,
            'type' => $type,
        ]);
    }

    /**
     * Record wallet debit
     */
    public function recordWalletDebit(
        int $amount,
        string $type
    ): void {
        $this->logger->channel('prometheus')->$this->logger->info('wallet_debit', [
            'amount' => $amount,
            'type' => $type,
        ]);
    }

    /**
     * Record circuit breaker state change
     */
    public function recordCircuitBreakerState(
        string $provider,
        string $state
    ): void {
        $this->logger->channel('prometheus')->$this->logger->info('circuit_breaker_state', [
            'provider' => $provider,
            'state' => $state,
        ]);
    }

    /**
     * Record payment latency
     */
    public function recordPaymentLatency(
        string $provider,
        string $operation,
        float $durationSeconds
    ): void {
        $this->logger->channel('prometheus')->$this->logger->info('payment_latency', [
            'provider' => $provider,
            'operation' => $operation,
            'duration_seconds' => $durationSeconds,
        ]);
    }
}
