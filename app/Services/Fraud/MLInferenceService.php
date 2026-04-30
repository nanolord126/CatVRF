<?php

declare(strict_types=1);

namespace App\Services\Fraud;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * ML Inference Service for Fraud Detection
 *
 * Provides real-time ML model inference with:
 * - ONNX runtime integration
 * - Circuit breaker pattern
 * - Async job fallback
 * - Model versioning
 */
final readonly class MLInferenceService
{
    use WithAuditLogging;

    private const CIRCUIT_BREAKER_KEY = 'fraud:ml:circuit_breaker';

    private const CIRCUIT_BREAKER_TTL = 300; // 5 minutes

    private const FAILURE_THRESHOLD = 5;

    private const MODEL_CACHE_TTL = 3600;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Repository $cache,
        private readonly LoggerInterface $logger,
        private readonly HttpFactory $http,
        private readonly RedisFactory $redis,
        private readonly AuditService $audit,
    ) {}

    /**
     * Perform ML inference on features
     * Returns fraud score (0.0 - 1.0)
     */
    public function predict(array $features, ?string $correlationId = null): float
    {
        $correlationId ??= Str::uuid()->toString();

        // Check circuit breaker
        if ($this->isCircuitOpen()) {
            $this->logger->channel('fraud_alert')->warning('ML circuit breaker is open, using fallback', [
                'correlation_id' => $correlationId,
            ]);

            return $this->getFallbackScore($features);
        }

        try {
            $score = $this->doInference($features);
            $this->recordSuccess();

            return $score;
        } catch (\Throwable $e) {
            $this->recordFailure();
            $this->logger->channel('fraud_alert')->error('ML inference failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackScore($features);
        }
    }

    /**
     * Reset circuit breaker (for admin/monitoring)
     */
    public function resetCircuitBreaker(): void
    {
        $this->redis->connection()->del(self::CIRCUIT_BREAKER_KEY);
        $this->redis->connection()->del('fraud:ml:failures');
        $this->logger->channel('fraud_alert')->$this->logger->info('ML circuit breaker reset manually');
    }

    /**
     * Get circuit breaker status
     */
    public function getCircuitBreakerStatus(): array
    {
        return [
            'is_open' => $this->isCircuitOpen(),
            'failures' => (int) $this->redis->connection()->get('fraud:ml:failures') ?: 0,
            'threshold' => self::FAILURE_THRESHOLD,
            'ttl' => $this->redis->connection()->ttl(self::CIRCUIT_BREAKER_KEY),
        ];
    }

    /**
     * Check if circuit breaker is open
     */
    private function isCircuitOpen(): bool
    {
        return (bool) $this->redis->connection()->get(self::CIRCUIT_BREAKER_KEY);
    }

    /**
     * Record successful inference
     */
    private function recordSuccess(): void
    {
        $key = 'fraud:ml:failures';
        $this->redis->connection()->del($key);
    }

    /**
     * Record failed inference and trigger circuit breaker if needed
     */
    private function recordFailure(): void
    {
        $key = 'fraud:ml:failures';
        $failures = (int) $this->redis->connection()->incr($key);

        if ($failures === 1) {
            $this->redis->connection()->expire($key, self::CIRCUIT_BREAKER_TTL);
        }

        if ($failures >= self::FAILURE_THRESHOLD) {
            $this->redis->connection()->setex(self::CIRCUIT_BREAKER_KEY, self::CIRCUIT_BREAKER_TTL, '1');
            $this->logger->channel('fraud_alert')->critical('ML circuit breaker opened due to failures', [
                'failures' => $failures,
            ]);
        }
    }

    /**
     * Perform actual ML inference
     * Tries ONNX runtime first, falls back to HTTP API
     */
    private function doInference(array $features): float
    {
        $modelVersion = $this->getCurrentModelVersion();

        // Try ONNX runtime (Python microservice)
        if ($this->config->get('fraud.ml.onnx_enabled', false)) {
            return $this->predictWithONNX($features, $modelVersion);
        }

        // Fallback to HTTP API
        return $this->predictWithHTTP($features, $modelVersion);
    }

    /**
     * Predict using ONNX runtime via Python microservice
     */
    private function predictWithONNX(array $features, string $modelVersion): float
    {
        $endpoint = $this->config->get('fraud.ml.onnx_endpoint', 'http://localhost:8000/predict');

        $response = $this->http->timeout(2)->post($endpoint, [
            'features' => $features,
            'model_version' => $modelVersion,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("ONNX inference failed: {$response->status()}");
        }

        $data = $response->json();

        return (float) ($data['score'] ?? 0.5);
    }

    /**
     * Predict using HTTP API (external ML service)
     */
    private function predictWithHTTP(array $features, string $modelVersion): float
    {
        $endpoint = $this->config->get('fraud.ml.http_endpoint');

        if (! $endpoint) {
            return $this->getFallbackScore($features);
        }

        $response = $this->http->timeout(1)->post($endpoint, [
            'features' => $features,
            'model_version' => $modelVersion,
            'api_key' => $this->config->get('fraud.ml.api_key'),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP ML inference failed: {$response->status()}");
        }

        $data = $response->json();

        return (float) ($data['fraud_score'] ?? 0.5);
    }

    /**
     * Fallback score based on rule-based heuristics
     */
    private function getFallbackScore(array $features): float
    {
        $score = 0.0;

        // Velocity-based scoring
        if (($features['transactions_5min'] ?? 0) >= 5) {
            $score += 0.35;
        }
        if (($features['transactions_1hour'] ?? 0) >= 10) {
            $score += 0.25;
        }

        // Amount-based scoring
        if (($features['amount'] ?? 0) >= 1_000_000) {
            $score += 0.40;
        }

        // Device/IP changes
        if (($features['device_changed_24h'] ?? false)) {
            $score += 0.20;
        }
        if (($features['ip_changed_24h'] ?? false)) {
            $score += 0.15;
        }

        // Failed attempts
        if (($features['failed_attempts_1hour'] ?? 0) >= 5) {
            $score += 0.45;
        }

        return min(1.0, max(0.0, $score));
    }

    /**
     * Get current ML model version
     */
    private function getCurrentModelVersion(): string
    {
        return $this->cache->remember('fraud:ml:current_version', self::MODEL_CACHE_TTL, function () {
            $modelPath = base_path('storage' . DIRECTORY_SEPARATOR . 'models/fraud');
            if (! is_dir($modelPath)) {
                return 'v1.0.0';
            }

            $models = array_filter(
                scandir($modelPath),
                fn ($f) => str_ends_with($f, '.onnx') || str_ends_with($f, '.joblib'),
            );

            if (empty($models)) {
                return 'v1.0.0';
            }

            usort($models, fn ($a, $b) => filemtime("$modelPath/$b") - filemtime("$modelPath/$a"));

            return pathinfo($models[0], PATHINFO_FILENAME);
        });
    }
}
