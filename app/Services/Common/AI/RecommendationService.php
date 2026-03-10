<?php

namespace App\Services\Common\AI;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Typesense\Client as TypesenseClient;

/**
 * 2026 AI Recommendation Engine for CatVRF Ecosystem.
 * Implements Vector Similarity, Geo-Awareness, and Behavioral Boosting.
 */
class RecommendationService
{
    protected TypesenseClient $typesense;
    protected string $embeddingModel = 'text-embedding-3-large';

    public function __construct(TypesenseClient $typesense)
    {
        $this->typesense = $typesense;
    }

    /**
     * Personalized recommendations for a specific user.
     * Uses hybrid approach: User Affinity + Recent Behavioral Context.
     */
    public function forUser(User $user, int $limit = 10): array
    {
        return Facades\Cache::remember("user_recs:{$user->id}", Carbon::now()->addHours(1), function () use ($user, $limit) {
            // 1. Get Top Affinities from Behavioral Telemetry (Redis/ClickHouse)
            $affinities = Redis::zrevrange("user:{$user->id}:affinity", 0, 5);
            $recentInteractions = Redis::lrange("user:{$user->id}:recent_views", 0, 10);

            // 2. Vector Search based on recent interaction context
            $contextQuery = !empty($recentInteractions) ? implode(' ', $recentInteractions) : ($affinities[0] ?? 'featured');
            $vector = $this->getEmbeddings($contextQuery);

            $searchParams = [
                'q' => '*',
                'vector_query' => "embeddings:({$vector}, k:{$limit})",
                'filter_by' => 'is_active:true',
                'sort_by' => '_vector_distance:asc',
            ];

            $results = $this->typesense->collections['marketplace_entities']->documents->search($searchParams);
            return $results['hits'];
        });
    }

    /**
     * Content-based filtering: find similar entities (Service to Service, etc.).
     */
    public function similarTo(Model $entity, int $limit = 6): array
    {
        $cacheKey = "similar_to:{$entity->getTable()}:{$entity->id}";

        return Facades\Cache::remember($cacheKey, Carbon::now()->addDay(), function () use ($entity, $limit) {
            // Retrieve stored vector for this entity
            $vector = Redis::get("vector:{$entity->getTable()}:{$entity->id}");

            if (!$vector) {
                $vector = $this->getEmbeddings($entity->name . ' ' . $entity->description);
            }

            $searchParams = [
                'q' => '*',
                'vector_query' => "embeddings:({$vector}, k:{$limit})",
                'filter_by' => "id:!={$entity->id} && tenant_id:={$entity->tenant_id}",
                'sort_by' => '_vector_distance:asc',
            ];

            $results = $this->typesense->collections['marketplace_entities']->documents->search($searchParams);
            return $results['hits'];
        });
    }

    /**
     * Geo-aware recommendations: Nearby services/masters with relevance boost.
     */
    public function geoNearby(float $lat, float $lng, int $radius = 5000, int $limit = 10): array
    {
        $searchParams = [
            'q' => '*',
            'filter_by' => "location:({$lat}, {$lng}, {$radius} m)",
            'sort_by' => "location({$lat}, {$lng}):asc, rating:desc",
            'per_page' => $limit
        ];

        $results = $this->typesense->collections['marketplace_entities']->documents->search($searchParams);
        return $results['hits'];
    }

    /**
     * Integration with OpenAI text-embedding-3-large.
     */
    public function getEmbeddings(string $text): string
    {
        return Facades\Cache::remember("emb:" . md5($text), Carbon::now()->addDays(30), function () use ($text) {
            $response = Http::withToken(config('services.openai.key'))
                ->post('https://api.openai.com/v1/embeddings', [
                    'input' => $text,
                    'model' => $this->embeddingModel,
                ])->throw()->json();

            $embedding = $response['data'][0]['embedding'] ?? [];
            return implode(',', $embedding);
        });
    }
}
