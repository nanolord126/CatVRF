<?php

namespace App\Services\Common;

use App\Models\Common\MarketplaceSearchIndex;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class MarketplaceAISearchService
{
    /**
     * Гибридный поиск: Vector (AI) + Full-Text
     */
    public function unifiedSearch(string $query, array $filters = [], array $geo = null)
    {
        // 1. В реальном 2026 году: Генерация эмбеддинга запроса через OpenAI
        // $embedding = OpenAI::embeddings()->create(['model' => 'text-embedding-3-small', 'input' => $query]);
        
        // 2. Имитация поиска с учетом AI-семантики и Гео
        // Мы ищем ближайшие точки и подходящие по смыслу категории (Цветы, Врачи и т.д.)
        $results = MarketplaceSearchIndex::query()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            });

        if ($geo) {
            // Упрощенный расчет дистанции (в реальности PostGIS ST_Distance)
            // $results->orderByRaw("ST_Distance(geo_point, ST_MakePoint(?, ?))", [$geo['lat'], $geo['lng']]);
        }

        return $results->orderBy('rating', 'desc')->limit(20)->get();
    }

    /**
     * Автоматическая индексация изменений (Embeddings Sync)
     */
    public function syncProductToIndex($model)
    {
        Log::info("AI Search Indexing: Syncing {$model->getTable()} ID #{$model->id}");
        
        // Тут AI генерирует "смысловое описание" для поиска по вектору
        // "Свежие розы с доставкой за 30 мин в ЦАО" -> Vector
        
        return MarketplaceSearchIndex::updateOrCreate(
            ['searchable_type' => get_class($model), 'searchable_id' => $model->id],
            [
                'tenant_id' => tenant('id') ?? 'system',
                'title' => $model->name ?? $model->title,
                'content' => $model->description ?? '',
                'geo_point' => $model->geo_location ?? null,
                'rating' => $model->rating ?? 5.0,
            ]
        );
    }
}
