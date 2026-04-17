<?php declare(strict_types=1);

namespace App\Services\Resilience;

use Psr\Log\LoggerInterface;
use Throwable;

trait RetryTrait
{
    /**
     * Execute a callback with retry logic for transient failures.
     *
     * @param callable $callback The operation to execute
     * @param int $maxAttempts Maximum number of retry attempts
     * @param int $initialDelayMs Initial delay in milliseconds
     * @param float $backoffMultiplier Multiplier for exponential backoff
     * @param array $retryableExceptions Exception classes that should trigger retry
     * @param string $operationName Name of the operation for logging
     * @return mixed The result of the callback
     * @throws Throwable If all retry attempts fail
     */
    protected function executeWithRetry(
        callable $callback,
        int $maxAttempts = 3,
        int $initialDelayMs = 100,
        float $backoffMultiplier = 2.0,
        array $retryableExceptions = [],
        string $operationName = 'operation'
    ): mixed {
        $attempt = 0;
        $delayMs = $initialDelayMs;
        $lastException = null;

        // Default retryable exceptions for common transient failures
        if (empty($retryableExceptions)) {
            $retryableExceptions = [
                \Illuminate\Http\Client\ConnectionException::class,
                \Illuminate\Http\Client\RequestException::class,
                \Illuminate\Http\Client\ConnectionTimeoutException::class,
                \GuzzleHttp\Exception\ConnectException::class,
                \GuzzleHttp\Exception\RequestException::class,
                \GuzzleHttp\Exception\ServerException::class,
                \GuzzleHttp\Exception\TooManyRedirectsException::class,
            ];
        }

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                return $callback();
            } catch (Throwable $e) {
                $lastException = $e;

                // Check if exception is retryable
                $isRetryable = false;
                foreach ($retryableExceptions as $exceptionClass) {
                    if ($e instanceof $exceptionClass) {
                        $isRetryable = true;
                        break;
                    }
                }

                // Also retry on specific error codes
                if (!$isRetryable) {
                    $isRetryable = $this->isRetryableError($e);
                }

                if (!$isRetryable || $attempt >= $maxAttempts) {
                    break;
                }

                // Log retry attempt
                if (isset($this->logger) && $this->logger instanceof LoggerInterface) {
                    $this->logger->warning("Retry attempt $attempt/$maxAttempts for $operationName", [
                        'error' => $e->getMessage(),
                        'delay_ms' => $delayMs,
                    ]);
                }

                // Wait before retry
                usleep($delayMs * 1000);
                $delayMs = (int)($delayMs * $backoffMultiplier);
            }
        }

        // All attempts failed
        if (isset($this->logger) && $this->logger instanceof LoggerInterface) {
            $this->logger->error("All $maxAttempts retry attempts failed for $operationName", [
                'error' => $lastException?->getMessage(),
            ]);
        }

        throw $lastException;
    }

    /**
     * Determine if an error is retryable based on error code or message.
     */
    protected function isRetryableError(Throwable $e): bool
    {
        $message = $e->getMessage();
        
        // Retry on common transient error indicators
        $retryablePatterns = [
            '/timeout/i',
            '/connection/i',
            '/502/i',
            '/503/i',
            '/504/i',
            '/429/i', // Too Many Requests
            '/ECONNRESET/i',
            '/ETIMEDOUT/i',
        ];

        foreach ($retryablePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }
}
