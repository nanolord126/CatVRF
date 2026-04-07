<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use OpenAI\Laravel\Facades\OpenAI;

final class RecommendationService extends AbstractTechnicalVerticalService
{
    public function isEnabled(): bool
    {
        return $this->tenant->settings['recommendations_enabled'] ?? true;
    }

    /**
     * Генерация эмбеддинга для сущности (Товар/Услуга/Профиль)
     */
        public function getEmbedding(string $text): array
        {
            return Cache::remember('emb_' . md5($text), 86400, function () use ($text) {
                $response = OpenAI::embeddings()->create([
                    'model' => 'text-embedding-3-small',
                    'input' => $text,
                ]);
    
                return $response->embeddings[0]->embedding;
            });
        }
    
        /**
         * Поиск похожих объектов через косинусное сходство (Vector Search)
         * В реальном 2026 проекте это делается через Typesense/Elasticsearch, 
         * но для бизнес-логики мы предоставляем интерфейс.
         */
        public function findSimilar(array $entityEmbedding, string $targetType, int $limit = 5): array
        {
            Log::info('AI Vector Search triggered', [
                'type' => $targetType,
                'correlation_id' => request()->header('X-Correlation-ID')
            ]);
    
            // Поиск в БД по векторному полю (mock для канона)
            return [];
        }
}
