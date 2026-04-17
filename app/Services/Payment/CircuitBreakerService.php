<?php declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Circuit Breaker Service for Payment Gateways
 * 
 * Implements the Circuit Breaker pattern to prevent cascading failures
 * when payment gateways are experiencing issues.
 * 
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Gateway is failing, requests are blocked immediately
 * - HALF_OPEN: Testing if gateway has recovered
 * 
 * Configuration:
 * - failureThreshold: Number of failures before opening circuit
 * - successThreshold: Number of successes before closing circuit
 * - timeoutSeconds: How long to stay in OPEN state before trying HALF_OPEN
 */
final readonly class CircuitBreakerService
{
    private const STATE_PREFIX = 'circuit_breaker:state:';
    private const FAILURE_COUNT_PREFIX = 'circuit_breaker:failures:';
    private const LAST_FAILURE_PREFIX = 'circuit_breaker:last_failure:';
    
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';
    
    private const DEFAULT_FAILURE_THRESHOLD = 5;
    private const DEFAULT_SUCCESS_THRESHOLD = 2;
    private const DEFAULT_TIMEOUT_SECONDS = 60;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LogManager $logger,
    ) {}

    /**
     * Check if circuit is open for a gateway
     * 
     * @param string $gateway Gateway name (tinkoff, sber, tochka)
     * @param int $failureThreshold Number of failures before opening
     * @param int $timeoutSeconds How long to stay open
     * @return bool True if circuit is OPEN (should block requests)
     */
    public function isOpen(
        string $gateway,
        int $failureThreshold = self::DEFAULT_FAILURE_THRESHOLD,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS
    ): bool {
        $state = $this->getState($gateway);

        if ($state === self::STATE_CLOSED) {
            return false;
        }

        if ($state === self::STATE_OPEN) {
            // Check if timeout has elapsed, try HALF_OPEN
            $lastFailure = $this->getLastFailureTime($gateway);
            
            if ($lastFailure && Carbon::parse($lastFailure)->addSeconds($timeoutSeconds)->isPast()) {
                $this->setState($gateway, self::STATE_HALF_OPEN);
                $this->logger->channel('audit')->info('Circuit breaker transitioning to HALF_OPEN', [
                    'gateway' => $gateway,
                    'time_since_failure' => Carbon::parse($lastFailure)->diffInSeconds() . 's',
                ]);
                return false;
            }

            return true;
        }

        // HALF_OPEN state - allow one request through
        return false;
    }

    /**
     * Record a successful request
     * 
     * @param string $gateway Gateway name
     * @param int $successThreshold Number of successes to close circuit
     * @return void
     */
    public function recordSuccess(
        string $gateway,
        int $successThreshold = self::DEFAULT_SUCCESS_THRESHOLD
    ): void {
        $state = $this->getState($gateway);

        if ($state === self::STATE_HALF_OPEN) {
            // In HALF_OPEN, we need consecutive successes to close
            $successCount = (int) $this->cache->get("{$this->getPrefix()}:{$gateway}:success_count", 0) + 1;
            $this->cache->put("{$this->getPrefix()}:{$gateway}:success_count", $successCount, now()->addMinutes(5));

            if ($successCount >= $successThreshold) {
                $this->setState($gateway, self::STATE_CLOSED);
                $this->resetFailures($gateway);
                $this->cache->forget("{$this->getPrefix()}:{$gateway}:success_count");
                
                $this->logger->channel('audit')->info('Circuit breaker CLOSED after successful recovery', [
                    'gateway' => $gateway,
                    'success_count' => $successCount,
                ]);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success in normal operation
            $this->resetFailures($gateway);
        }
    }

    /**
     * Record a failed request
     * 
     * @param string $gateway Gateway name
     * @param int $failureThreshold Number of failures before opening
     * @return void
     */
    public function recordFailure(
        string $gateway,
        int $failureThreshold = self::DEFAULT_FAILURE_THRESHOLD
    ): void {
        $failureCount = $this->getFailureCount($gateway) + 1;
        $this->cache->put($this->getFailureKey($gateway), $failureCount, now()->addHours(1));
        $this->cache->put($this->getLastFailureKey($gateway), now()->toIso8601String(), now()->addHours(1));

        $state = $this->getState($gateway);

        if ($state === self::STATE_HALF_OPEN) {
            // Failure in HALF_OPEN means not recovered, go back to OPEN
            $this->setState($gateway, self::STATE_OPEN);
            $this->cache->forget("{$this->getPrefix()}:{$gateway}:success_count");
            
            $this->logger->channel('audit')->warning('Circuit breaker returned to OPEN after HALF_OPEN failure', [
                'gateway' => $gateway,
                'failure_count' => $failureCount,
            ]);
        } elseif ($failureCount >= $failureThreshold) {
            // Threshold reached, open the circuit
            $this->setState($gateway, self::STATE_OPEN);
            
            $this->logger->channel('audit')->critical('Circuit breaker OPENED due to failures', [
                'gateway' => $gateway,
                'failure_count' => $failureCount,
                'threshold' => $failureThreshold,
            ]);
        } else {
            $this->logger->channel('audit')->warning('Circuit breaker recording failure', [
                'gateway' => $gateway,
                'failure_count' => $failureCount,
                'threshold' => $failureThreshold,
            ]);
        }
    }

    /**
     * Get current state of circuit breaker
     * 
     * @param string $gateway Gateway name
     * @return string State (closed, open, half_open)
     */
    public function getState(string $gateway): string
    {
        return $this->cache->get($this->getStateKey($gateway), self::STATE_CLOSED);
    }

    /**
     * Manually close circuit breaker (for admin/recovery)
     * 
     * @param string $gateway Gateway name
     * @return void
     */
    public function close(string $gateway): void
    {
        $this->setState($gateway, self::STATE_CLOSED);
        $this->resetFailures($gateway);
        $this->cache->forget("{$this->getPrefix()}:{$gateway}:success_count");
        
        $this->logger->channel('audit')->info('Circuit breaker manually CLOSED', [
            'gateway' => $gateway,
        ]);
    }

    /**
     * Manually open circuit breaker (for maintenance)
     * 
     * @param string $gateway Gateway name
     * @return void
     */
    public function open(string $gateway): void
    {
        $this->setState($gateway, self::STATE_OPEN);
        $this->cache->put($this->getLastFailureKey($gateway), now()->toIso8601String(), now()->addHours(1));
        
        $this->logger->channel('audit')->info('Circuit breaker manually OPENED', [
            'gateway' => $gateway,
        ]);
    }

    /**
     * Get circuit breaker stats for monitoring
     * 
     * @param string $gateway Gateway name
     * @return array Stats array
     */
    public function getStats(string $gateway): array
    {
        return [
            'gateway' => $gateway,
            'state' => $this->getState($gateway),
            'failure_count' => $this->getFailureCount($gateway),
            'last_failure' => $this->getLastFailureTime($gateway),
        ];
    }

    /**
     * Set circuit breaker state
     */
    private function setState(string $gateway, string $state): void
    {
        $this->cache->put($this->getStateKey($gateway), $state, now()->addHours(24));
    }

    /**
     * Get failure count
     */
    private function getFailureCount(string $gateway): int
    {
        return (int) $this->cache->get($this->getFailureKey($gateway), 0);
    }

    /**
     * Reset failure count
     */
    private function resetFailures(string $gateway): void
    {
        $this->cache->forget($this->getFailureKey($gateway));
    }

    /**
     * Get last failure time
     */
    private function getLastFailureTime(string $gateway): ?string
    {
        return $this->cache->get($this->getLastFailureKey($gateway));
    }

    /**
     * Build state key
     */
    private function getStateKey(string $gateway): string
    {
        return self::STATE_PREFIX . $gateway;
    }

    /**
     * Build failure count key
     */
    private function getFailureKey(string $gateway): string
    {
        return self::FAILURE_COUNT_PREFIX . $gateway;
    }

    /**
     * Build last failure key
     */
    private function getLastFailureKey(string $gateway): string
    {
        return self::LAST_FAILURE_PREFIX . $gateway;
    }

    /**
     * Get prefix for additional keys
     */
    private function getPrefix(): string
    {
        return 'circuit_breaker';
    }
}
