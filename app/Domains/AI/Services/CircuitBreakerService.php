<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Circuit Breaker Service for External AI API Calls
 *
 * Prevents cascade failures when external AI services (OpenAI, etc.) are down.
 * Implements state machine: CLOSED -> OPEN -> HALF_OPEN -> CLOSED
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Circuit is tripped, requests fail fast
 * - HALF_OPEN: Testing if service is recovered
 *
 * Strictly follows CatVRF rules:
 * - No facades, uses dependency injection
 * - Comprehensive logging for observability
 * - Configurable thresholds per service
 * - Automatic recovery with half-open state
 */
final readonly class CircuitBreakerService
{
    private const string STATE_PREFIX = 'circuit_breaker:';
    private const string FAILURE_COUNT_PREFIX = 'circuit_breaker_failures:';
    private const string LAST_FAILURE_PREFIX = 'circuit_breaker_last_failure:';
    private const string SUCCESS_COUNT_PREFIX = 'circuit_breaker_successes:';
    private const string HALF_OPEN_ATTEMPTS_PREFIX = 'circuit_breaker_half_open_attempts:';
    
    private const int DEFAULT_THRESHOLD = 5;
    private const int DEFAULT_TIMEOUT = 60;
    private const int DEFAULT_HALF_OPEN_ATTEMPTS = 3;
    private const int DEFAULT_TTL = 3600;

    /**
     * Execute operation with circuit breaker protection
     *
     * @param string $service Service identifier (e.g., 'openai_vision', 'openai_chat')
     * @param callable $operation Operation to execute
     * @param int $threshold Failure threshold
     * @param int $timeout Timeout in seconds
     * @return mixed Operation result
     * @throws RuntimeException If circuit is open or operation fails
     */
    public function call(
        string $service,
        callable $operation,
        int $threshold = self::DEFAULT_THRESHOLD,
        int $timeout = self::DEFAULT_TIMEOUT
    ): mixed {
        $state = $this->getState($service);

        $this->logger->debug('Circuit breaker call attempt', [
            'service' => $service,
            'state' => $state,
            'threshold' => $threshold,
            'timeout' => $timeout,
        ]);

        if ($state === 'OPEN') {
            $lastFailure = $this->getLastFailureTime($service);
            $timeSinceFailure = time() - $lastFailure;
            
            if ($timeSinceFailure > $timeout) {
                $this->setState($service, 'HALF_OPEN');
                $this->logger->info('Circuit breaker transitioned to HALF_OPEN', [
                    'service' => $service,
                    'time_since_failure' => $timeSinceFailure,
                ]);
            } else {
                $remainingTime = $timeout - $timeSinceFailure;
                $this->logger->warning('Circuit breaker is OPEN, rejecting request', [
                    'service' => $service,
                    'remaining_time' => $remainingTime,
                ]);
                
                throw new RuntimeException(
                    sprintf('Circuit breaker is OPEN for service: %s. Try again in %d seconds.', 
                        $service, 
                        $remainingTime
                    )
                );
            }
        }

        try {
            $result = $operation();
            
            if ($state === 'HALF_OPEN') {
                $this->incrementSuccesses($service);
                $successCount = $this->getSuccessCount($service);
                
                if ($successCount >= self::DEFAULT_HALF_OPEN_ATTEMPTS) {
                    $this->reset($service);
                    $this->logger->info('Circuit breaker recovered to CLOSED', [
                        'service' => $service,
                        'success_count' => $successCount,
                    ]);
                }
            } else {
                $this->decrementFailures($service);
            }
            
            $this->logger->debug('Circuit breaker call successful', [
                'service' => $service,
                'state' => $this->getState($service),
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->incrementFailures($service);
            $this->setLastFailureTime($service);
            $this->resetSuccesses($service);
            
            $failureCount = $this->getFailureCount($service);
            
            if ($failureCount >= $threshold) {
                $this->setState($service, 'OPEN');
                $this->logger->error('Circuit breaker opened due to failures', [
                    'service' => $service,
                    'failure_count' => $failureCount,
                    'threshold' => $threshold,
                    'error' => $e->getMessage(),
                ]);
            } else {
                $this->logger->warning('Circuit breaker recorded failure', [
                    'service' => $service,
                    'failure_count' => $failureCount,
                    'threshold' => $threshold,
                    'error' => $e->getMessage(),
                ]);
            }
            
            throw new RuntimeException(
                sprintf('Service %s failed: %s', $service, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * Get current circuit state
     */
    private function getState(string $service): string
    {
        return $this->cache->get(self::STATE_PREFIX . $service, 'CLOSED');
    }

    /**
     * Set circuit state
     */
    private function setState(string $service, string $state): void
    {
        $this->cache->put(self::STATE_PREFIX . $service, $state, self::DEFAULT_TTL);
    }

    /**
     * Get failure count
     */
    private function getFailureCount(string $service): int
    {
        return (int) $this->cache->get(self::FAILURE_COUNT_PREFIX . $service, 0);
    }

    /**
     * Increment failure count
     */
    private function incrementFailures(string $service): void
    {
        $key = self::FAILURE_COUNT_PREFIX . $service;
        $current = $this->cache->get($key, 0);
        $this->cache->put($key, $current + 1, self::DEFAULT_TTL);
    }

    /**
     * Decrement failure count
     */
    private function decrementFailures(string $service): void
    {
        $count = $this->getFailureCount($service);
        if ($count > 0) {
            $this->cache->put(self::FAILURE_COUNT_PREFIX . $service, $count - 1, self::DEFAULT_TTL);
        }
    }

    /**
     * Get last failure time
     */
    private function getLastFailureTime(string $service): int
    {
        return (int) $this->cache->get(self::LAST_FAILURE_PREFIX . $service, 0);
    }

    /**
     * Set last failure time
     */
    private function setLastFailureTime(string $service): void
    {
        $this->cache->put(self::LAST_FAILURE_PREFIX . $service, time(), self::DEFAULT_TTL);
    }

    /**
     * Get success count (for half-open state)
     */
    private function getSuccessCount(string $service): int
    {
        return (int) $this->cache->get(self::SUCCESS_COUNT_PREFIX . $service, 0);
    }

    /**
     * Increment success count
     */
    private function incrementSuccesses(string $service): void
    {
        $key = self::SUCCESS_COUNT_PREFIX . $service;
        $current = $this->cache->get($key, 0);
        $this->cache->put($key, $current + 1, self::DEFAULT_TTL);
    }

    /**
     * Reset success count
     */
    private function resetSuccesses(string $service): void
    {
        $this->cache->forget(self::SUCCESS_COUNT_PREFIX . $service);
    }

    /**
     * Reset circuit breaker to CLOSED state
     */
    public function reset(string $service): void
    {
        $this->cache->forget(self::STATE_PREFIX . $service);
        $this->cache->forget(self::FAILURE_COUNT_PREFIX . $service);
        $this->cache->forget(self::LAST_FAILURE_PREFIX . $service);
        $this->cache->forget(self::SUCCESS_COUNT_PREFIX . $service);
        
        $this->logger->info('Circuit breaker reset to CLOSED', ['service' => $service]);
    }

    /**
     * Manually open circuit (for maintenance)
     */
    public function open(string $service): void
    {
        $this->setState($service, 'OPEN');
        $this->setLastFailureTime($service);
        
        $this->logger->info('Circuit breaker manually opened', ['service' => $service]);
    }

    /**
     * Manually close circuit (for recovery)
     */
    public function close(string $service): void
    {
        $this->reset($service);
    }

    /**
     * Get circuit status for monitoring
     */
    public function getStatus(string $service): array
    {
        $lastFailureTime = $this->getLastFailureTime($service);
        
        return [
            'service' => $service,
            'state' => $this->getState($service),
            'failure_count' => $this->getFailureCount($service),
            'success_count' => $this->getSuccessCount($service),
            'last_failure' => $lastFailureTime,
            'last_failure_human' => $lastFailureTime > 0 
                ? date('Y-m-d H:i:s', $lastFailureTime) 
                : null,
            'time_since_failure' => $lastFailureTime > 0 ? time() - $lastFailureTime : null,
        ];
    }

    /**
     * Get all circuit breaker statuses for monitoring dashboard
     */
    public function getAllStatuses(array $services): array
    {
        $statuses = [];
        
        foreach ($services as $service) {
            $statuses[$service] = $this->getStatus($service);
        }
        
        return $statuses;
    }

    /**
     * Check if circuit is open for a service
     */
    public function isOpen(string $service): bool
    {
        return $this->getState($service) === 'OPEN';
    }

    /**
     * Check if circuit is in half-open state
     */
    public function isHalfOpen(string $service): bool
    {
        return $this->getState($service) === 'HALF_OPEN';
    }

    /**
     * Check if circuit is closed
     */
    public function isClosed(string $service): bool
    {
        return $this->getState($service) === 'CLOSED';
    }

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger
    ) {}
}
