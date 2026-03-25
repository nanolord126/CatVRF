<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Social\Models\SocialPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис управления AI-рекомендациями (Раздел 6)
 */
final class RecommendationService
{
    private string $correlationId;

    public function __construct(?string $correlationId = null)
    {
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
     * Имитация запроса к ML-модели (Python/TensorFlow)
     */
    private function getAIPredictions(int $userId, int $limit): Collection
    {
        try {
            // В 2026 тут запрос к отдельному ML-API
            // Http::post(config('services.ml.url') . '/forecast', ['user_id' => $userId]);
            
            return collect([]); // Пока пусто
        } catch (\Exception $e) {
            $this->log->error('AI Recommendation failed: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Логирование события просмотра поста для обучения ML
     */
    public function logView(int $userId, int $postId): void
    {
        $this->log->channel('audit')->info('User viewed post', [
            'user_id' => $userId,
            'post_id' => $postId,
            'correlation_id' => $this->correlationId,
        ]);

        // Хранить в ClickHouse в реальном проекте
    }
}
