<?php

declare(strict_types=1);

namespace App\Octane\Services;

use Illuminate\Log\LogManager;
use Swoole\Coroutine as Co;

final readonly class SwooleCoroutineService
{
    /**
     * Execute callback in coroutine context if Swoole is available
     */
    public function runInCoroutine(callable $callback, float $timeout = 30.0): mixed
    {
        if (! function_exists('Swoole\Coroutine\run')) {
            // Fallback to synchronous execution if Swoole coroutines not available
            return $callback();
        }

        return Co\run(function () use ($callback, $timeout) {
            return Co\setTimeout(function () use ($callback) {
                return $callback();
            }, $timeout);
        });
    }

    /**
     * Execute multiple callbacks in parallel using coroutines
     */
    public function runParallel(array $callbacks, float $timeout = 30.0): array
    {
        if (! function_exists('Swoole\Coroutine\run')) {
            // Fallback to sequential execution
            $results = [];
            foreach ($callbacks as $callback) {
                $results[] = $callback();
            }

            return $results;
        }

        return Co\run(function () use ($callbacks, $timeout) {
            $results = [];
            $channels = [];

            foreach ($callbacks as $key => $callback) {
                $channels[$key] = new Co\Channel();

                Co\create(function () use ($callback, $channels, $key, $timeout) {
                    try {
                        $result = Co\setTimeout(function () use ($callback) {
                            return $callback();
                        }, $timeout);
                        $channels[$key]->push($result);
                    } catch (\Throwable $e) {
                        $this->log->error('Coroutine execution failed', [
                            'key' => $key,
                            'error' => $e->getMessage(),
                        ]);
                        $channels[$key]->push(null);
                    }
                });
            }

            // Collect results
            foreach ($channels as $key => $channel) {
                $results[$key] = $channel->pop();
            }

            return $results;
        });
    }

    /**
     * Sleep in coroutine context (non-blocking)
     */
    public function sleep(float $seconds): void
    {
        if (function_exists('Swoole\Coroutine::sleep')) {
            Co::sleep($seconds);
        } else {
            usleep((int) ($seconds * 1_000_000));
        }
    }

    /**
     * Get current coroutine ID
     */
    public function getCoroutineId(): int|string
    {
        if (function_exists('Swoole\Coroutine::getCid')) {
            return Co::getCid();
        }

        return 'none';
    }

    /**
     * Check if running in coroutine context
     */
    public function inCoroutine(): bool
    {
        return function_exists('Swoole\Coroutine::getCid') && Co::getCid() > 0;
    }
}
