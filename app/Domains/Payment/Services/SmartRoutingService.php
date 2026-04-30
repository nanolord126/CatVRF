<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * Smart Routing Service - intelligent gateway selection.
 *
 * Routes payments to optimal gateway based on:
 * - Client type (B2C/B2B)
 * - Amount thresholds
 * - Fraud risk score
 * - Regional preferences
 * - Gateway health (circuit breaker)
 * - Cost optimization
 */
final readonly class SmartRoutingService
{
    private const string CIRCUIT_STATE_PREFIX = 'payment:circuit:';
    private const string METRICS_PREFIX = 'payment:metrics:';

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Select the best gateway for a payment.
     *
     * @return array{provider: string, confidence: float, reason: string}
     */
    public function selectGateway(
        MoneyVO $amount,
        PaymentMethodVO $paymentMethod,
        string $clientType, // 'b2c' or 'b2b'
        ?float $fraudScore = null,
        ?string $region = null,
        ?string $preferredProvider = null,
    ): array {
        $availableGateways = $this->getAvailableGateways($paymentMethod);

        if ($availableGateways->isEmpty()) {
            throw new \RuntimeException('No available gateways for the given payment method');
        }

        // If preferred provider is available and healthy, use it
        if ($preferredProvider && $availableGateways->contains($preferredProvider) && $this->isGatewayHealthy($preferredProvider)) {
            $this->logger->info('Smart routing: using preferred provider', [
                'provider' => $preferredProvider,
                'reason' => 'preferred_provider',
            ]);

            return [
                'provider' => $preferredProvider,
                'confidence' => 1.0,
                'reason' => 'preferred_provider',
            ];
        }

        // Score each gateway
        $scores = $availableGateways->map(fn (string $provider) => [
            'provider' => $provider,
            'score' => $this->scoreGateway(
                $provider,
                $amount,
                $paymentMethod,
                $clientType,
                $fraudScore,
                $region,
            ),
        ]);

        // Sort by score descending
        $scores = $scores->sortByDesc('score');

        $best = $scores->first();

        $this->logger->info('Smart routing: selected gateway', [
            'provider' => $best['provider'],
            'score' => $best['score'],
            'reason' => 'smart_selection',
        ]);

        return [
            'provider' => $best['provider'],
            'confidence' => $best['score'] / 100,
            'reason' => 'smart_selection',
        ];
    }

    /**
     * Get fallback gateway if primary fails.
     */
    public function getFallbackGateway(
        string $failedProvider,
        PaymentMethodVO $paymentMethod,
    ): ?string {
        $availableGateways = $this->getAvailableGateways($paymentMethod)
            ->filter(fn (string $provider) => $provider !== $failedProvider)
            ->filter(fn (string $provider) => $this->isGatewayHealthy($provider));

        return $availableGateways->first();
    }

    /**
     * Get available gateways for a payment method.
     */
    private function getAvailableGateways(PaymentMethodVO $paymentMethod): Collection
    {
        $gatewaysByMethod = [
            'card' => ['tinkoff', 'tochka', 'sber'],
            'sbp' => ['tinkoff', 'sber'],
            'sber_pay' => ['sber'],
            'installments' => ['tinkoff', 'tochka'],
            'bank_transfer' => ['tochka'],
        ];

        $gateways = collect($gatewaysByMethod[$paymentMethod->method] ?? ['tinkoff']);

        // Filter by health (circuit breaker)
        return $gateways->filter(fn (string $provider) => $this->isGatewayHealthy($provider));
    }

    /**
     * Check if gateway is healthy (circuit breaker not open).
     */
    private function isGatewayHealthy(string $provider): bool
    {
        $key = self::CIRCUIT_STATE_PREFIX.$provider;
        $state = $this->redis->connection()->get($key);

        return $state !== 'open';
    }

    /**
     * Score a gateway based on multiple factors (0-100).
     */
    private function scoreGateway(
        string $provider,
        MoneyVO $amount,
        PaymentMethodVO $paymentMethod,
        string $clientType,
        ?float $fraudScore,
        ?string $region,
    ): float {
        $score = 0.0;

        // 1. Client type fit (30 points)
        $score += $this->scoreByClientType($provider, $clientType);

        // 2. Amount fit (20 points)
        $score += $this->scoreByAmount($provider, $amount);

        // 3. Payment method fit (20 points)
        $score += $this->scoreByPaymentMethod($provider, $paymentMethod);

        // 4. Fraud risk handling (15 points)
        $score += $this->scoreByFraudRisk($provider, $fraudScore);

        // 5. Regional fit (10 points)
        $score += $this->scoreByRegion($provider, $region);

        // 6. Performance metrics (5 points)
        $score += $this->scoreByPerformance($provider);

        return min($score, 100.0);
    }

    /**
     * Score based on client type (B2C vs B2B).
     */
    private function scoreByClientType(string $provider, string $clientType): float
    {
        return match ([$provider, $clientType]) {
            ['tinkoff', 'b2c'] => 30.0,
            ['tochka', 'b2b'] => 30.0,
            ['sber', 'b2c'] => 25.0,
            ['tinkoff', 'b2b'] => 20.0,
            ['tochka', 'b2c'] => 15.0,
            ['sber', 'b2b'] => 20.0,
            default => 10.0,
        };
    }

    /**
     * Score based on amount thresholds.
     */
    private function scoreByAmount(string $provider, MoneyVO $amount): float
    {
        $amountRubles = $amount->toMajorUnits();

        // Tinkoff is good for medium amounts
        if ($provider === 'tinkoff' && $amountRubles >= 1000 && $amountRubles <= 500000) {
            return 20.0;
        }

        // Tochka is good for large B2B amounts
        if ($provider === 'tochka' && $amountRubles >= 10000) {
            return 20.0;
        }

        // Sber is good for all amounts
        if ($provider === 'sber') {
            return 18.0;
        }

        return 10.0;
    }

    /**
     * Score based on payment method support.
     */
    private function scoreByPaymentMethod(string $provider, PaymentMethodVO $paymentMethod): float
    {
        $support = [
            'tinkoff' => ['card', 'sbp', 'sber_pay', 'installments'],
            'tochka' => ['card', 'bank_transfer', 'installments'],
            'sber' => ['card', 'sbp', 'sber_pay'],
        ];

        if (in_array($paymentMethod->method, $support[$provider] ?? [], true)) {
            return 20.0;
        }

        return 5.0;
    }

    /**
     * Score based on fraud risk handling.
     */
    private function scoreByFraudRisk(string $provider, ?float $fraudScore): float
    {
        if ($fraudScore === null) {
            return 7.5; // Neutral score
        }

        // Tinkoff has strong fraud detection
        if ($provider === 'tinkoff' && $fraudScore > 0.7) {
            return 15.0;
        }

        // Sber also good for high risk
        if ($provider === 'sber' && $fraudScore > 0.7) {
            return 12.0;
        }

        return 7.5;
    }

    /**
     * Score based on regional preferences.
     */
    private function scoreByRegion(string $provider, ?string $region): float
    {
        if ($region === null) {
            return 5.0;
        }

        // Region-specific preferences could be configured
        // For now, give equal scores
        return 5.0;
    }

    /**
     * Score based on performance metrics (latency, success rate).
     */
    private function scoreByPerformance(string $provider): float
    {
        $key = self::METRICS_PREFIX.$provider.':success_rate';
        $successRate = (float) $this->redis->connection()->get($key) ?: 0.95;

        return $successRate * 5.0;
    }

    /**
     * Record gateway success for metrics.
     */
    public function recordSuccess(string $provider, float $latencyMs): void
    {
        $this->recordMetric($provider, 'success_count', 1);
        $this->recordMetric($provider, 'total_count', 1);
        $this->recordMetric($provider, 'latency_sum', $latencyMs);
        $this->updateSuccessRate($provider);
    }

    /**
     * Record gateway failure for metrics.
     */
    public function recordFailure(string $provider, float $latencyMs): void
    {
        $this->recordMetric($provider, 'failure_count', 1);
        $this->recordMetric($provider, 'total_count', 1);
        $this->recordMetric($provider, 'latency_sum', $latencyMs);
        $this->updateSuccessRate($provider);
    }

    private function recordMetric(string $provider, string $metric, float $value): void
    {
        $key = self::METRICS_PREFIX.$provider.':'.$metric;
        $this->redis->connection()->incrbyfloat($key, $value);
        $this->redis->connection()->expire($key, 86400); // 24 hours TTL
    }

    private function updateSuccessRate(string $provider): void
    {
        $successKey = self::METRICS_PREFIX.$provider.':success_count';
        $totalKey = self::METRICS_PREFIX.$provider.':total_count';
        $rateKey = self::METRICS_PREFIX.$provider.':success_rate';

        $success = (float) $this->redis->connection()->get($successKey) ?: 0;
        $total = (float) $this->redis->connection()->get($totalKey) ?: 1;

        $rate = $total > 0 ? $success / $total : 0.95;
        $this->redis->connection()->setex($rateKey, 86400, (string) $rate);
    }
}
