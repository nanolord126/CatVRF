<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Load Testing Service
 * Инструменты для нагрузочного тестирования и стресс-тестирования API
 * 
 * @package App\Services\Performance
 * @category Performance / Testing
 */
final class LoadTestingService
{
    /**
     * Симулирует нагрузку на эндпоинт
     * 
     * @param string $url
     * @param int $requestsCount
     * @param int $concurrency
     * @param array $headers
     * @return array {total_requests, successful, failed, average_response_time, p99, p95}
     */
    public static function stressTest(
        string $url,
        int $requestsCount = 100,
        int $concurrency = 10,
        array $headers = []
    ): array {
        $startTime = microtime(true);
        $responseTimes = [];
        $successful = 0;
        $failed = 0;

        // Добавляем токен авторизации по умолчанию
        $headers['Authorization'] = $headers['Authorization'] ?? 'Bearer token';

        $this->log->channel('performance')->info('Load test started', [
            'url' => $url,
            'requests' => $requestsCount,
            'concurrency' => $concurrency
        ]);

        // Отправляем запросы параллельно
        $promises = [];
        for ($i = 0; $i < $requestsCount; $i++) {
            $promise = Http::asJson()
                ->withHeaders($headers)
                ->timeout(30)
                ->getAsync($url)
                ->then(
                    function ($response) use (&$responseTimes, &$successful) {
                        $responseTimes[] = $response->getElapsedTime() * 1000; // мс
                        $successful++;
                    },
                    function ($error) use (&$failed) {
                        $failed++;
                        $this->log->channel('performance')->warning('Load test request failed', [
                            'error' => $error->getMessage()
                        ]);
                    }
                );

            $promises[] = $promise;

            // Соблюдаем concurrency limit
            if (count($promises) >= $concurrency) {
                \Illuminate\Support\Facades\Http::pool(fn ($pool) => $promises);
                $promises = [];
            }
        }

        // Завершаем оставшиеся запросы
        if (!empty($promises)) {
            \Illuminate\Support\Facades\Http::pool(fn ($pool) => $promises);
        }

        $totalTime = microtime(true) - $startTime;

        // Вычисляем метрики
        sort($responseTimes);
        $avgTime = !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0;
        $p99 = self::percentile($responseTimes, 99);
        $p95 = self::percentile($responseTimes, 95);
        $minTime = min($responseTimes) ?? 0;
        $maxTime = max($responseTimes) ?? 0;

        $results = [
            'total_requests' => $requestsCount,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => ($successful / $requestsCount) * 100,
            'total_time_seconds' => round($totalTime, 2),
            'requests_per_second' => round($requestsCount / $totalTime, 2),
            'average_response_time_ms' => round($avgTime, 2),
            'p99_response_time_ms' => round($p99, 2),
            'p95_response_time_ms' => round($p95, 2),
            'min_response_time_ms' => round($minTime, 2),
            'max_response_time_ms' => round($maxTime, 2),
        ];

        $this->log->channel('performance')->info('Load test completed', $results);

        return $results;
    }

    /**
     * Тестирует throughput (пропускную способность)
     * 
     * @param string $url
     * @param int $duration
     * @return array
     */
    public static function throughputTest(string $url, int $duration = 60): array
    {
        $startTime = microtime(true);
        $requestCount = 0;
        $dataTransferred = 0;

        while ((microtime(true) - $startTime) < $duration) {
            try {
                $response = \Illuminate\Support\Facades\Http::get($url);
                $requestCount++;
                $dataTransferred += strlen($response->body());
            } catch (\Throwable $e) {
                $this->log->channel('performance')->warning('Throughput test error', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $elapsed = microtime(true) - $startTime;
        $throughputMbps = ($dataTransferred * 8 / 1000000) / $elapsed; // Mbps

        return [
            'duration_seconds' => round($elapsed, 2),
            'total_requests' => $requestCount,
            'throughput_mbps' => round($throughputMbps, 2),
            'data_transferred_mb' => round($dataTransferred / 1000000, 2),
        ];
    }

    /**
     * Тестирует latency (задержку)
     * 
     * @param string $url
     * @param int $samples
     * @return array
     */
    public static function latencyTest(string $url, int $samples = 100): array
    {
        $latencies = [];

        for ($i = 0; $i < $samples; $i++) {
            $start = microtime(true);
            
            try {
                \Illuminate\Support\Facades\Http::get($url);
                $latencies[] = (microtime(true) - $start) * 1000; // мс
            } catch (\Throwable $e) {
                $this->log->channel('performance')->warning('Latency test error', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        sort($latencies);

        return [
            'samples' => count($latencies),
            'min_ms' => round(min($latencies), 2),
            'max_ms' => round(max($latencies), 2),
            'avg_ms' => round(array_sum($latencies) / count($latencies), 2),
            'p50_ms' => round(self::percentile($latencies, 50), 2),
            'p95_ms' => round(self::percentile($latencies, 95), 2),
            'p99_ms' => round(self::percentile($latencies, 99), 2),
        ];
    }

    /**
     * Вычисляет процентиль
     * 
     * @param array $values
     * @param int $percentile
     * @return float
     */
    private static function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return $values[$lower];
        }

        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }

    /**
     * Генерирует отчёт о performance
     * 
     * @param array $results
     * @return string
     */
    public static function generateReport(array $results): string
    {
        $report = "\n╔════════════════════════════════════════════════════════════╗\n";
        $report .= "║               LOAD TEST REPORT                             ║\n";
        $report .= "║               " . now()->toDateTimeString() . "                    ║\n";
        $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

        foreach ($results as $key => $value) {
            $label = str_replace('_', ' ', ucfirst($key));
            $report .= sprintf("  %-40s: %s\n", $label, $value);
        }

        $report .= "\n";

        return $report;
    }
}
