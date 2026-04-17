<?php declare(strict_types=1);

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

final class ApiTelemetryService
{
    private array $metrics = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Record an API call with telemetry data.
     *
     * @param string $service Service name (e.g., 'openai', 'payment_gateway')
     * @param string $operation Operation name (e.g., 'chat', 'init_payment')
     * @param bool $success Whether the call was successful
     * @param int $responseTimeMs Response time in milliseconds
     * @param array $additionalData Additional context data
     */
    public function recordApiCall(
        string $service,
        string $operation,
        bool $success,
        int $responseTimeMs,
        array $additionalData = []
    ): void {
        $metric = [
            'service' => $service,
            'operation' => $operation,
            'success' => $success,
            'response_time_ms' => $responseTimeMs,
            'timestamp' => microtime(true),
            'correlation_id' => $additionalData['correlation_id'] ?? null,
            'user_id' => $additionalData['user_id'] ?? null,
            'error' => $additionalData['error'] ?? null,
            'status_code' => $additionalData['status_code'] ?? null,
        ];

        $this->metrics[] = $metric;

        // Log individual call for debugging
        $logLevel = $success ? 'info' : 'warning';
        $this->logger->$logLevel("API call telemetry: {$service}::{$operation}", array_filter($metric, fn($v) => $v !== null));

        // Flush metrics if buffer is full
        if (count($this->metrics) >= 100) {
            $this->flushMetrics();
        }
    }

    /**
     * Record a successful API call.
     */
    public function recordSuccess(
        string $service,
        string $operation,
        int $responseTimeMs,
        array $additionalData = []
    ): void {
        $this->recordApiCall($service, $operation, true, $responseTimeMs, $additionalData);
    }

    /**
     * Record a failed API call.
     */
    public function recordFailure(
        string $service,
        string $operation,
        int $responseTimeMs,
        string $error,
        array $additionalData = []
    ): void {
        $additionalData['error'] = $error;
        $this->recordApiCall($service, $operation, false, $responseTimeMs, $additionalData);
    }

    /**
     * Flush accumulated metrics to storage/log.
     */
    public function flushMetrics(): void
    {
        if (empty($this->metrics)) {
            return;
        }

        // Aggregate metrics
        $aggregated = $this->aggregateMetrics();

        // Log aggregated metrics
        $this->logger->info('API telemetry metrics flushed', [
            'total_calls' => count($this->metrics),
            'aggregated' => $aggregated,
        ]);

        // Clear buffer
        $this->metrics = [];
    }

    /**
     * Get aggregated metrics by service and operation.
     */
    public function aggregateMetrics(): array
    {
        $aggregated = [];

        foreach ($this->metrics as $metric) {
            $key = "{$metric['service']}::{$metric['operation']}";
            
            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'service' => $metric['service'],
                    'operation' => $metric['operation'],
                    'total_calls' => 0,
                    'successful_calls' => 0,
                    'failed_calls' => 0,
                    'total_response_time_ms' => 0,
                    'avg_response_time_ms' => 0,
                    'min_response_time_ms' => PHP_INT_MAX,
                    'max_response_time_ms' => 0,
                ];
            }

            $aggregated[$key]['total_calls']++;
            $aggregated[$key]['total_response_time_ms'] += $metric['response_time_ms'];
            $aggregated[$key]['min_response_time_ms'] = min(
                $aggregated[$key]['min_response_time_ms'],
                $metric['response_time_ms']
            );
            $aggregated[$key]['max_response_time_ms'] = max(
                $aggregated[$key]['max_response_time_ms'],
                $metric['response_time_ms']
            );

            if ($metric['success']) {
                $aggregated[$key]['successful_calls']++;
            } else {
                $aggregated[$key]['failed_calls']++;
            }
        }

        // Calculate averages
        foreach ($aggregated as &$data) {
            if ($data['total_calls'] > 0) {
                $data['avg_response_time_ms'] = $data['total_response_time_ms'] / $data['total_calls'];
                $data['success_rate'] = ($data['successful_calls'] / $data['total_calls']) * 100;
            }
        }

        return array_values($aggregated);
    }

    /**
     * Execute a callable with automatic telemetry recording.
     *
     * @param string $service Service name
     * @param string $operation Operation name
     * @param callable $callback The operation to execute
     * @param array $additionalData Additional context data
     * @return mixed The result of the callback
     * @throws \Throwable If the callback throws an exception
     */
    public function withTelemetry(
        string $service,
        string $operation,
        callable $callback,
        array $additionalData = []
    ): mixed {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            
            $this->recordSuccess($service, $operation, $responseTimeMs, $additionalData);
            
            return $result;
        } catch (\Throwable $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            
            $this->recordFailure(
                $service,
                $operation,
                $responseTimeMs,
                $e->getMessage(),
                $additionalData
            );
            
            throw $e;
        }
    }
}
