<?php declare(strict_types=1);

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Throwable;

/**
 * ExternalApiErrorHandlerService - Centralized error handling for external API calls
 * 
 * Provides consistent error handling, logging, and retry logic for all external API calls.
 * Implements circuit breaker pattern and handles rate limits, timeouts, and network errors.
 */
final readonly class ExternalApiErrorHandlerService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;
    private const RATE_LIMIT_RETRY_DELAY_MS = 5000;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    /**
     * Execute external API call with comprehensive error handling
     *
     * @param string $serviceName Name of the service being called (e.g., 'openai', 'stripe')
     * @param callable $apiCall The API call to execute
     * @param array $context Additional context for logging
     * @param int $timeoutMs Timeout in milliseconds
     * @param bool $useCircuitBreaker Whether to use circuit breaker
     * @return mixed The API response
     * @throws \RuntimeException On failure after retries or circuit breaker open
     */
    public function execute(
        string $serviceName,
        callable $apiCall,
        array $context = [],
        int $timeoutMs = 30000,
        bool $useCircuitBreaker = true,
    ): mixed {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
        $context['correlation_id'] = $correlationId;
        $context['service'] = $serviceName;

        $this->logger->info('external_api.call.start', $context);

        if ($useCircuitBreaker && !$this->circuitBreaker->isAvailable($serviceName)) {
            $this->logger->warning('external_api.circuit_breaker_open', $context);
            throw new \RuntimeException(
                sprintf('Service %s is temporarily unavailable due to circuit breaker', $serviceName),
                503
            );
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;
            $context['attempt'] = $attempt;

            try {
                $result = $apiCall();
                
                $this->logger->info('external_api.call.success', array_merge($context, [
                    'attempt' => $attempt,
                ]));

                if ($useCircuitBreaker) {
                    $this->circuitBreaker->recordSuccess($serviceName);
                }

                return $result;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e;
                $context['error_type'] = 'connection_error';
                $context['error_message'] = $e->getMessage();
                
                $this->logger->error('external_api.connection_error', $context);

                if ($attempt < self::MAX_RETRIES) {
                    $this->sleep(self::RETRY_DELAY_MS * $attempt);
                    continue;
                }
            } catch (\Illuminate\Http\Client\TimeoutException $e) {
                $lastError = $e;
                $context['error_type'] = 'timeout';
                $context['error_message'] = $e->getMessage();
                
                $this->logger->error('external_api.timeout', $context);

                if ($attempt < self::MAX_RETRIES) {
                    $this->sleep(self::RETRY_DELAY_MS * $attempt);
                    continue;
                }
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $lastError = $e;
                $response = $e->response;
                
                $context['error_type'] = 'http_error';
                $context['status_code'] = $response?->status();
                $context['error_message'] = $e->getMessage();

                // Handle rate limiting (429)
                if ($response && $response->status() === 429) {
                    $this->logger->warning('external_api.rate_limit', $context);
                    
                    if ($attempt < self::MAX_RETRIES) {
                        $retryAfter = $response->header('Retry-After');
                        $delayMs = $retryAfter ? ((int)$retryAfter * 1000) : self::RATE_LIMIT_RETRY_DELAY_MS;
                        $this->sleep($delayMs);
                        continue;
                    }
                }

                // Handle client errors (4xx) - don't retry
                if ($response && $response->status() >= 400 && $response->status() < 500) {
                    $this->logger->error('external_api.client_error', $context);
                    
                    if ($useCircuitBreaker) {
                        $this->circuitBreaker->recordFailure($serviceName);
                    }

                    throw $this->createClientErrorException($serviceName, $response, $correlationId);
                }

                // Handle server errors (5xx) - retry
                if ($response && $response->status() >= 500) {
                    $this->logger->error('external_api.server_error', $context);
                    
                    if ($attempt < self::MAX_RETRIES) {
                        $this->sleep(self::RETRY_DELAY_MS * $attempt);
                        continue;
                    }
                }

                $this->logger->error('external_api.request_error', $context);
            } catch (\RuntimeException $e) {
                $lastError = $e;
                $context['error_type'] = 'runtime_error';
                $context['error_message'] = $e->getMessage();
                
                // Check if it's a circuit breaker error
                if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                    $this->logger->warning('external_api.circuit_breaker_triggered', $context);
                    throw $e;
                }

                $this->logger->error('external_api.runtime_error', $context);

                if ($attempt < self::MAX_RETRIES) {
                    $this->sleep(self::RETRY_DELAY_MS * $attempt);
                    continue;
                }
            } catch (Throwable $e) {
                $lastError = $e;
                $context['error_type'] = 'unexpected_error';
                $context['error_message'] = $e->getMessage();
                $context['error_class'] = get_class($e);
                
                $this->logger->error('external_api.unexpected_error', $context);

                if ($attempt < self::MAX_RETRIES) {
                    $this->sleep(self::RETRY_DELAY_MS * $attempt);
                    continue;
                }
            }
        }

        // All retries failed
        if ($useCircuitBreaker) {
            $this->circuitBreaker->recordFailure($serviceName);
        }

        $this->logger->critical('external_api.all_retries_failed', array_merge($context, [
            'total_attempts' => $attempt,
        ]));

        throw new \RuntimeException(
            sprintf(
                'Failed to call %s after %d attempts. Last error: %s',
                $serviceName,
                $attempt,
                $lastError?->getMessage() ?? 'Unknown error'
            ),
            503,
            $lastError
        );
    }

    /**
     * Create a formatted client error exception
     */
    private function createClientErrorException(
        string $serviceName,
        Response $response,
        string $correlationId,
    ): \RuntimeException {
        $status = $response->status();
        $body = $response->body();
        
        $message = match ($status) {
            400 => sprintf('Bad request to %s. Correlation ID: %s', $serviceName, $correlationId),
            401 => sprintf('Unauthorized access to %s. Correlation ID: %s', $serviceName, $correlationId),
            403 => sprintf('Forbidden access to %s. Correlation ID: %s', $serviceName, $correlationId),
            404 => sprintf('Resource not found in %s. Correlation ID: %s', $serviceName, $correlationId),
            422 => sprintf('Validation error from %s: %s. Correlation ID: %s', $serviceName, $body, $correlationId),
            429 => sprintf('Rate limit exceeded for %s. Correlation ID: %s', $serviceName, $correlationId),
            default => sprintf('Client error %d from %s. Correlation ID: %s', $status, $serviceName, $correlationId),
        };

        return new \RuntimeException($message, $status);
    }

    /**
     * Sleep for specified milliseconds
     */
    private function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }

    /**
     * Create a configured HTTP client with timeout and retry settings
     */
    public function createHttpClient(string $serviceName, int $timeoutMs = 30000): PendingRequest
    {
        return \Illuminate\Support\Facades\Http::timeout($timeoutMs / 1000)
            ->withHeaders([
                'X-Correlation-ID' => Str::uuid()->toString(),
                'X-Service-Name' => config('app.name'),
            ])
            ->retry(3, function ($attempt, $exception) use ($serviceName) {
                $shouldRetry = $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || $exception instanceof \Illuminate\Http\Client\TimeoutException
                    || ($exception->response?->status() ?? 0) >= 500;

                if ($shouldRetry) {
                    $this->logger->warning('external_api.auto_retry', [
                        'service' => $serviceName,
                        'attempt' => $attempt,
                        'error' => $exception->getMessage(),
                    ]);
                }

                return $shouldRetry;
            }, 1000);
    }
}
