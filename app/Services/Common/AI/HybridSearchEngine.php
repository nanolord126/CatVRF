<?php

namespace App\Services\Common\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use Typesense\Client as TypesenseClient;

class HybridSearchEngine
{
    protected TypesenseClient $typesense;
    
    // 2026 Ranking Weights
    const WEIGHT_SEMANTIC = 0.40;
    const WEIGHT_GEO      = 0.30;
    const WEIGHT_BEHAVIOR = 0.20;
    const WEIGHT_KEYWORD  = 0.10;

    public function __construct(TypesenseClient $typesense)
    {
        $this->typesense = $typesense;
    }

    /**
     * Executes the hybrid search pipeline: 
     * Vector (OpenAI) + Geo + Behavioral (Redis/BigData) + Full-Text (Typesense)
     */
    public function search(
        string $query, 
        ?User $user = null, 
        ?array $geo = ['lat' => 0, 'lng' => 0, 'radius' => 5000], 
        string $collection = 'marketplace_entities',
        string $mode = 'full'
    ): array {
        $cacheKey = "hybrid_search:" . md5($query . ($user?->id ?? 'guest') . json_encode($geo) . $mode);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $user, $geo, $collection, $mode) {
            
            // Если режим 'lightweight' (активен при нагрузке 100k+ RPM), отключаем тяжелую генерацию эмбеддингов
            if ($mode === 'lightweight') {
                $searchParameters = [
                    'q' => $query,
                    'query_by' => 'name,description,category',
                    'filter_by' => "location:({$geo['lat']}, {$geo['lng']}, {$geo['radius']} m)",
                ];
                $results = $this->typesense->collections[$collection]->documents->search($searchParameters);
                return $results['hits']; 
            }

            // 1. Generate Query Embeddings (Semantic Vector) - ТЯЖЕЛАЯ ОПЕРАЦИЯ
            $vector = $this->generateEmbeddings($query);

            // 2. Fetch Behavioral Boosts (User History from Redis/BigData)
            $behavioralContext = $this->getUserBehavioralContext($user);

            // 3. Construct Typesense Hybrid Query
            $searchParameters = [
                'q' => $query,
                'query_by' => 'name,description,category',
                'vector_query' => "embeddings:({$vector}, k:50)",
                'filter_by' => "location:({$geo['lat']}, {$geo['lng']}, {$geo['radius']} m)",
                'sort_by' => '_text_match:desc, _vector_distance:asc',
                'prioritize_exact_match' => true,
                'highlight_full_fields' => 'name',
            ];

            $results = $this->typesense->collections[$collection]->documents->search($searchParameters);

            // 4. Custom Re-ranking (Applying 2026 Weights)
            return $this->rerankResults($results['hits'], $geo, $behavioralContext);
        });
    }

    protected function generateEmbeddings(string $text): string
    {
        // Integration with text-embedding-3-large
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/embeddings', [
                'input' => $text,
                'model' => 'text-embedding-3-large',
            ])->json();

        return implode(',', $response['data'][0]['embedding'] ?? []);
    }

    protected function getUserBehavioralContext(?User $user): array
    {
        if (!$user) return [];
        
        // Fetch top categories/tags from Redis user history (Behavioral telemetry)
        return Redis::zrevrange("user:{$user->id}:affinity", 0, 5, ['WITHSCORES' => true]);
    }

    protected function rerankResults(array $hits, array $geo, array $behavior): array
    {
        return collect($hits)->map(function ($hit) use ($geo, $behavior) {
            $doc = $hit['document'];
            
            // 40% Semantic (Normalized 1 - distance)
            $semanticScore = (1 - ($hit['vector_distance'] ?? 0.5)) * self::WEIGHT_SEMANTIC;
            
            // 30% Geo (Normalized distance to radius)
            $distMeters = $hit['geo_distance_meters'] ?? 5000;
            $geoScore = (1 - min(1, $distMeters / $geo['radius'])) * self::WEIGHT_GEO;
            
            // 20% Behavioral Boost
            $affinityBoost = isset($behavior[$doc['category']]) ? 1.0 : 0.0;
            $behaviorScore = $affinityBoost * self::WEIGHT_BEHAVIOR;
            
            // 10% Keyword relevance
            $keywordScore = ($hit['text_match'] / 100) * self::WEIGHT_KEYWORD;

            $doc['_total_relevance'] = $semanticScore + $geoScore + $behaviorScore + $keywordScore;
            
            return $doc;
        })->sortByDesc('_total_relevance')->values()->all();
    }
}
