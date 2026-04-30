<?php

declare(strict_types=1);

namespace Modules\Recommendation\Infrastructure\ML;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Interfaces\MLInferenceInterface;

final readonly class MLInferenceClient implements MLInferenceInterface
{
    private const TIMEOUT_SECONDS = 5;
    private const RETRY_ATTEMPTS = 2;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    public function getCandidates(int $tenantId, array $userFeatures, int $limit = 200, ?string $vertical = null): array
    {
        $endpoint = $this->baseUrl . '/v1/inference/candidates';

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(self::RETRY_ATTEMPTS, 100)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'tenant_id' => $tenantId,
                    'user_features' => $userFeatures,
                    'limit' => $limit,
                    'vertical' => $vertical,
                ]);

            if (!$response->successful()) {
                Log::warning('ML inference candidates failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'tenant_id' => $tenantId,
                ]);

                return $this->getFallbackCandidates($tenantId, $limit, $vertical);
            }

            $data = $response->json();

            return $data['candidates'] ?? [];
        } catch (\Throwable $e) {
            Log::error('ML inference candidates error', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return $this->getFallbackCandidates($tenantId, $limit, $vertical);
        }
    }

    public function rankItems(int $tenantId, array $userFeatures, array $itemFeatures, array $contextFeatures): array
    {
        $endpoint = $this->baseUrl . '/v1/inference/rank';

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(self::RETRY_ATTEMPTS, 100)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'tenant_id' => $tenantId,
                    'user_features' => $userFeatures,
                    'item_features' => $itemFeatures,
                    'context_features' => $contextFeatures,
                ]);

            if (!$response->successful()) {
                Log::warning('ML inference ranking failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'tenant_id' => $tenantId,
                ]);

                return $this->getFallbackRanking($itemFeatures);
            }

            $data = $response->json();

            return $data['ranked_items'] ?? [];
        } catch (\Throwable $e) {
            Log::error('ML inference ranking error', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return $this->getFallbackRanking($itemFeatures);
        }
    }

    public function getEmbeddings(string $entityType, int $entityId, int $tenantId): array
    {
        $endpoint = $this->baseUrl . '/v1/embeddings/' . $entityType . '/' . $entityId;

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(self::RETRY_ATTEMPTS, 100)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($endpoint, [
                    'tenant_id' => $tenantId,
                ]);

            if (!$response->successful()) {
                Log::warning('ML inference embeddings failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ]);

                return $this->getFallbackEmbedding();
            }

            $data = $response->json();

            return $data['embedding'] ?? [];
        } catch (\Throwable $e) {
            Log::error('ML inference embeddings error', [
                'error' => $e->getMessage(),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);

            return $this->getFallbackEmbedding();
        }
    }

    public function healthCheck(): array
    {
        $endpoint = $this->baseUrl . '/health';

        try {
            $response = Http::timeout(2)
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => 'healthy',
                    'model_version' => $data['model_version'] ?? 'unknown',
                    'latency_ms' => $data['latency_ms'] ?? 0,
                    'timestamp' => now()->toIso8601String(),
                ];
            }

            return [
                'status' => 'unhealthy',
                'model_version' => 'unknown',
                'error' => 'HTTP ' . $response->status(),
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unreachable',
                'model_version' => 'unknown',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    public function getModelVersion(): string
    {
        $health = $this->healthCheck();

        return $health['model_version'] ?? 'unknown';
    }

    private function getFallbackCandidates(int $tenantId, int $limit, ?string $vertical): array
    {
        $candidates = [];

        for ($i = 0; $i < min($limit, 50); $i++) {
            $candidates[] = [
                'item_id' => 1000 + $i,
                'seller_id' => 100 + ($i % 10),
                'vertical' => $vertical ?? 'unknown',
                'score' => max(0.1, 1.0 - ($i * 0.02)),
                'confidence' => 0.6,
                'position' => $i,
            ];
        }

        return $candidates;
    }

    private function getFallbackRanking(array $itemFeatures): array
    {
        $ranked = [];

        foreach ($itemFeatures as $idx => $item) {
            $score = $item['score'] ?? (1.0 - ($idx * 0.01));

            $ranked[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => max(0.0, $score),
                'confidence' => 0.7,
                'position' => $idx,
                'vertical' => $item['vertical'] ?? 'unknown',
            ];
        }

        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);

        foreach ($ranked as $idx => &$item) {
            $item['position'] = $idx;
        }

        return $ranked;
    }

    private function getFallbackEmbedding(): array
    {
        return array_fill(0, 128, 0.0);
    }

    public function batchGetCandidates(int $tenantId, array $userIds, int $limit = 200, ?string $vertical = null): array
    {
        $endpoint = $this->baseUrl . '/v1/inference/candidates/batch';

        try {
            $response = Http::timeout(10)
                ->retry(self::RETRY_ATTEMPTS, 200)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'tenant_id' => $tenantId,
                    'user_ids' => $userIds,
                    'limit' => $limit,
                    'vertical' => $vertical,
                ]);

            if (!$response->successful()) {
                Log::warning('ML inference batch candidates failed', [
                    'status' => $response->status(),
                    'tenant_id' => $tenantId,
                ]);

                return $this->getBatchFallbackCandidates($tenantId, $userIds, $limit, $vertical);
            }

            $data = $response->json();

            return $data['results'] ?? [];
        } catch (\Throwable $e) {
            Log::error('ML inference batch candidates error', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return $this->getBatchFallbackCandidates($tenantId, $userIds, $limit, $vertical);
        }
    }

    private function getBatchFallbackCandidates(int $tenantId, array $userIds, int $limit, ?string $vertical): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            $results[$userId] = $this->getFallbackCandidates($tenantId, $limit, $vertical);
        }

        return $results;
    }

    public function trainModel(string $modelType, array $config): array
    {
        $endpoint = $this->baseUrl . '/v1/models/train';

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'model_type' => $modelType,
                    'config' => $config,
                ]);

            if (!$response->successful()) {
                Log::error('ML model training failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model_type' => $modelType,
                ]);

                return [
                    'success' => false,
                    'error' => 'Training request failed',
                    'job_id' => null,
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'job_id' => $data['job_id'] ?? null,
                'estimated_time_minutes' => $data['estimated_time_minutes'] ?? 0,
            ];
        } catch (\Throwable $e) {
            Log::error('ML model training error', [
                'error' => $e->getMessage(),
                'model_type' => $modelType,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'job_id' => null,
            ];
        }
    }

    public function getTrainingStatus(string $jobId): array
    {
        $endpoint = $this->baseUrl . '/v1/models/train/' . $jobId;

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($endpoint);

            if (!$response->successful()) {
                return [
                    'job_id' => $jobId,
                    'status' => 'unknown',
                    'progress' => 0,
                    'error' => 'Status check failed',
                ];
            }

            $data = $response->json();

            return [
                'job_id' => $jobId,
                'status' => $data['status'] ?? 'unknown',
                'progress' => $data['progress'] ?? 0,
                'model_version' => $data['model_version'] ?? null,
                'metrics' => $data['metrics'] ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'job_id' => $jobId,
                'status' => 'error',
                'progress' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
}
