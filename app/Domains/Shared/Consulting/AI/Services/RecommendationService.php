<?php

declare(strict_types=1);

namespace App\Domains\Consulting\AI\Services;

use Illuminate\Http\Client\Factory as HttpClientFactory;

use Illuminate\Support\Collection;

use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final readonly class RecommendationService
{
    private readonly string $correlationId;

    public function __construct(private readonly HttpClientFactory $http,
        ?string $correlationId,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger) {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Получить ленту рекомендаций (Shorts/Посты)
     */
    public function getFeed(int $userId, int $tenantId, int $limit = 10): Collection
    {
        // 1. Пытаемся получить векторные рекомендации
        $recommendedIds = $this->getAIPredictions($userId, $limit);

        if ($recommendedIds->isNotEmpty()) {
            return SocialPost::whereIn('id', $recommendedIds)
                ->where('tenant_id', $tenantId)
                ->where('transcoding_status', 'completed')
                ->get();
        }

        // 2. Fallback: Трендовые (по лайкам)
        return SocialPost::where('tenant_id', $tenantId)
            ->where('transcoding_status', 'completed')
            ->orderByDesc('like_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Логирование события просмотра поста для обучения ML
     */
    public function logView(int $userId, int $postId): void
    {
        $this->logger->$this->logger->info('User viewed post', [
            'user_id' => $userId,
            'post_id' => $postId,
            'correlation_id' => $this->correlationId,
        ]);

        // Хранить в ClickHouse в реальном проекте
    }

    /**
     * Имитация запроса к ML-модели (Python/TensorFlow)
     */
    private function getAIPredictions(int $userId, int $limit): Collection
    {
        try {
            // В 2026 тут запрос к отдельному ML-API
            // $this->http->post($this->config->get('services.ml.url') . '/forecast', ['user_id' => $userId]);

            return new Collection([]); // Пока пусто
        } catch (\Throwable $e) {
            $this->logger->error('AI Recommendation failed: '.$e->getMessage());

            return new Collection([]);
        }
    }
}
