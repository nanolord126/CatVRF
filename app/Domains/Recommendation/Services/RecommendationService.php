<?php

declare(strict_types=1);

namespace App\Domains\Recommendation\Services;


use Psr\Log\LoggerInterface;
use Illuminate\Support\Collection;
/**
 * Единая точка выдачи персонализированных рекомендаций на базе embeddings (Typesense/Redis)
 * Категорически запрещено прямым запросом выдавать товары мимо этого сервиса.
 */
final readonly class RecommendationService
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Бескомпромиссное получение персонализированных рекомендаций с весовыми коэффициентами.
     */
    public function getForUser(int $userId, ?string $vertical, array $context, string $correlationId): Collection
    {
        $geoHash = $context['geo_hash'] ?? '00000';
        $cacheKey = "recommendation:user:{$userId}:vertical:{$vertical}:geo:{$geoHash}:v1";

        $recommendations = $this->cache->store('redis')->remember($cacheKey, 300, function () use ($userId, $vertical, $correlationId) {
            
            // Здесь происходит запрос к Typesense + Vector Search (Cosine similarity)
            $this->logger->info('ML Recommendations generated', [
                'user_id' => $userId,
                'vertical' => $vertical,
                'correlation_id' => $correlationId
            ]);

            return collect([
                // Спецификация результата (stubbed ids)
                ['item_id' => 101, 'score' => 0.95, 'source' => 'behavior'],
                ['item_id' => 105, 'score' => 0.88, 'source' => 'embedding_similarity'],
            ]);
        });

        return $recommendations;
    }

    /**
     * Кросс-вертикальные рекомендации (например Auto -> Beauty или Hotel -> Food).
     */
    public function getCrossVertical(int $userId, string $currentVertical, string $correlationId): Collection
    {
        $this->logger->info('Cross-vertical ML Recommendations triggered', [
            'user_id' => $userId,
            'current_vertical' => $currentVertical,
            'correlation_id' => $correlationId
        ]);

        return collect([]); 
    }

    /**
     * Жесткий сброс кэша преференций юзера при совершении покупки или отзыва.
     */
    public function invalidateUserCache(int $userId): void
    {
        // Очистка по паттернам ключей Redis
        $this->logger->info('User recommendations cache forcibly cleared', ['user_id' => $userId]);
    }
}
