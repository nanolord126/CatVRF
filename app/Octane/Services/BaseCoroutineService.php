<?php

declare(strict_types=1);

namespace App\Octane\Services;

use Illuminate\Log\LogManager;
use Throwable;

/**
 * Base coroutine-safe service for all verticals
 *
 * Provides common patterns for parallel execution in Octane/Swoole environment.
 * Verticals should extend this or use SwooleCoroutineService directly.
 */
abstract class BaseCoroutineService
{
    protected readonly SwooleCoroutineService $coroutineService;

    public function __construct(SwooleCoroutineService $coroutineService)
    {
        $this->coroutineService = $coroutineService;
    }

    /**
     * Execute multiple operations in parallel
     *
     * @param  array<string, callable>  $operations  Key-value pairs of operation name and callback
     * @param  float  $timeout  Timeout in seconds
     * @return array<string, mixed> Results keyed by operation name
     */
    protected function executeParallel(array $operations, float $timeout = 30.0): array
    {
        return $this->coroutineService->runParallel($operations, $timeout);
    }

    /**
     * Execute single operation in coroutine context
     */
    protected function executeInCoroutine(callable $callback, float $timeout = 30.0): mixed
    {
        return $this->coroutineService->runInCoroutine($callback, $timeout);
    }

    /**
     * Non-blocking sleep
     */
    protected function sleep(float $seconds): void
    {
        $this->coroutineService->sleep($seconds);
    }

    /**
     * Check if running in coroutine context
     */
    protected function isInCoroutine(): bool
    {
        return $this->coroutineService->inCoroutine();
    }

    /**
     * Graceful degradation: try coroutine, fallback to sequential
     *
     * @param  array<string, callable>  $operations
     * @return array<string, mixed>
     */
    protected function executeParallelWithFallback(array $operations, float $timeout = 30.0): array
    {
        if ($this->isInCoroutine()) {
            try {
                return $this->executeParallel($operations, $timeout);
            } catch (Throwable $e) {
                $this->log->warning('Coroutine execution failed, falling back to sequential', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sequential fallback
        $results = [];
        foreach ($operations as $key => $operation) {
            try {
                $results[$key] = $operation();
            } catch (Throwable $e) {
                $this->log->error("Operation {$key} failed", [
                    'error' => $e->getMessage(),
                ]);
                $results[$key] = null;
            }
        }

        return $results;
    }

    /**
     * Common pattern: AI API calls in parallel
     *
     * @param  array<string, string>  $prompts  Key-value pairs of prompt name and content
     * @param  callable  $aiCall  Function that takes prompt and returns result
     * @return array<string, mixed>
     */
    protected function executeAICallsInParallel(
        array $prompts,
        callable $aiCall,
        float $timeout = 30.0
    ): array {
        $operations = [];
        foreach ($prompts as $name => $prompt) {
            $operations[$name] = fn () => $aiCall($prompt);
        }

        return $this->executeParallelWithFallback($operations, $timeout);
    }

    /**
     * Common pattern: External API calls in parallel
     *
     * @param  array<string, array>  $requests  Key-value pairs of request name and parameters
     * @param  callable  $apiCall  Function that takes parameters and returns result
     * @return array<string, mixed>
     */
    protected function executeAPICallsInParallel(
        array $requests,
        callable $apiCall,
        float $timeout = 30.0
    ): array {
        $operations = [];
        foreach ($requests as $name => $params) {
            $operations[$name] = fn () => $apiCall($params);
        }

        return $this->executeParallelWithFallback($operations, $timeout);
    }

    /**
     * Common pattern: Database queries in parallel
     *
     * @param  array<string, callable>  $queries  Key-value pairs of query name and callback
     * @return array<string, mixed>
     */
    protected function executeQueriesInParallel(
        array $queries,
        float $timeout = 30.0
    ): array {
        return $this->executeParallelWithFallback($queries, $timeout);
    }
}
