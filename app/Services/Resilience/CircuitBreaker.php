<?php declare(strict_types=1);

namespace App\Services\Resilience;

use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;
use Throwable;

final class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private readonly Repository $cache,
        private readonly LoggerInterface $logger,
        private readonly string $service,
        private readonly int $failureThreshold = 5,
        private readonly int $timeoutSeconds = 60,
        private readonly int $halfOpenMaxCalls = 3
    ) {}

    /**
     * Execute a callback with circuit breaker protection.
     *
     * @throws Throwable If circuit is open or operation fails
     */
    public function call(callable $callback): mixed
    {
        $state = $this->getState();
        $failureCount = $this->getFailureCount();

        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset()) {
                $this->setState(self::STATE_HALF_OPEN);
                $this->logger->info("Circuit breaker transitioning to HALF_OPEN for {$this->service}");
            } else {
                $this->logger->warning("Circuit breaker OPEN for {$this->service}, rejecting call");
                throw new \RuntimeException("Circuit breaker is open for {$this->service}");
            }
        }

        try {
            $result = $callback();
            $this->onSuccess();
            return $result;
        } catch (Throwable $e) {
            $this->onFailure();
            throw $e;
        }
    }

    private function getState(): string
    {
        return $this->cache->get($this->getStateKey(), self::STATE_CLOSED);
    }

    private function setState(string $state): void
    {
        $this->cache->put($this->getStateKey(), $state, $this->timeoutSeconds * 2);
    }

    private function getFailureCount(): int
    {
        return (int) $this->cache->get($this->getFailureCountKey(), 0);
    }

    private function incrementFailureCount(): void
    {
        $this->cache->increment($this->getFailureCountKey());
        $this->cache->put($this->getFailureCountKey(), $this->getFailureCount(), $this->timeoutSeconds);
    }

    private function resetFailureCount(): void
    {
        $this->cache->forget($this->getFailureCountKey());
    }

    private function onSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $halfOpenCalls = $this->getHalfOpenCalls();
            $halfOpenCalls++;

            if ($halfOpenCalls >= $this->halfOpenMaxCalls) {
                $this->setState(self::STATE_CLOSED);
                $this->resetFailureCount();
                $this->resetHalfOpenCalls();
                $this->logger->info("Circuit breaker CLOSED for {$this->service}");
            } else {
                $this->setHalfOpenCalls($halfOpenCalls);
            }
        } else {
            $this->resetFailureCount();
        }
    }

    private function onFailure(): void
    {
        $failureCount = $this->getFailureCount();
        $failureCount++;
        $this->incrementFailureCount();

        $this->logger->error("Circuit breaker failure {$failureCount}/{$this->failureThreshold} for {$this->service}");

        if ($failureCount >= $this->failureThreshold) {
            $this->setState(self::STATE_OPEN);
            $this->cache->put($this->getOpenedAtKey(), time(), $this->timeoutSeconds);
            $this->logger->error("Circuit breaker OPENED for {$this->service}");
        }
    }

    private function shouldAttemptReset(): bool
    {
        $openedAt = $this->cache->get($this->getOpenedAtKey());
        if ($openedAt === null) {
            return true;
        }

        return (time() - $openedAt) >= $this->timeoutSeconds;
    }

    private function getHalfOpenCalls(): int
    {
        return (int) $this->cache->get($this->getHalfOpenCallsKey(), 0);
    }

    private function setHalfOpenCalls(int $count): void
    {
        $this->cache->put($this->getHalfOpenCallsKey(), $count, $this->timeoutSeconds);
    }

    private function resetHalfOpenCalls(): void
    {
        $this->cache->forget($this->getHalfOpenCallsKey());
    }

    private function getStateKey(): string
    {
        return "circuit_breaker:state:{$this->service}";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:failures:{$this->service}";
    }

    private function getOpenedAtKey(): string
    {
        return "circuit_breaker:opened_at:{$this->service}";
    }

    private function getHalfOpenCallsKey(): string
    {
        return "circuit_breaker:half_open_calls:{$this->service}";
    }
}
