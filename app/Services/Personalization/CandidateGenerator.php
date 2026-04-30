<?php

declare(strict_types=1);

namespace App\Services\Personalization;

use Illuminate\Support\Collection;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Client\Factory as HttpFactory;

final readonly class CandidateGenerator
{
    private const CANDIDATE_CACHE_TTL = 3600; // 1 hour
    private const EMBEDDING_DIMENSION = 128;

    public function __construct(
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Generate candidate recommendations using Two-Tower architecture
     *
     * @return $this->colle->ion<int, mixed>
     */
    public function getTopK(array $userFeatures, string $vertical, int $k): Collection
    {
        $cacheKey = "candidates:{$vertical}:{$k}:" . md5(json_encode($userFeatures));

        return Cache::remember($cacheKey, self::CANDIDATE_CACHE_TTL, function () use ($userFeatures, $vertical, $k) {
            // Get user embedding
            $userEmbedding = $this->getUserEmbedding($userFeatures);

            // Get item embeddings for the vertical
            $itemEmbeddings = $this->getItemEmbeddings($vertical, $k * 10);

            // Calculate similarity scores
            $candidates = $this->calculateSimilarityScores($userEmbedding, $itemEmbeddings);

            // Apply diversity filter
            $diverse = $this->applyDiversityFilter($candidates, $k * 2);

            // Return top K
            return $diverse->take($k);
        });
    }

    /**
     * Get similar items based on item-item similarity
     *
     * @return Collection<int, mixed>
     */
    public function getSimilarItems(array $itemFeatures, string $vertical, int $limit): Collection
    {
        $itemEmbedding = $this->getItemEmbedding($itemFeatures);
        $itemEmbeddings = $this->getItemEmbeddings($vertical, $limit * 5);

        $similarities = $this->calculateSimilarityScores($itemEmbedding, $itemEmbeddings);

        return $similarities->take($limit);
    }

    /**
     * Get user embedding from features
     */
    private function getUserEmbedding(array $features): array
    {
        // Try ML model first
        try {
            $response = $this->http->timeout(1)->post(
                config('personalization.embedding_endpoint', 'http://localhost:8001/embed/user'),
                ['features' => $features]
            );

            if ($response->successful()) {
                return $response->json('embedding', []);
            }
        } catch (\Exception $e) {
            // Fallback to rule-based embedding
        }

        return $this->generateRuleBasedUserEmbedding($features);
    }

    /**
     * Get item embeddings for a vertical
     *
     * @return Collection<int, array{id: int, embedding: array, type: string}>
     */
    private function getItemEmbeddings(string $vertical, int $limit): Collection
    {
        $entityType = $this->getEntityTypeForVertical($vertical);

        $items = $entityType::where('is_active', true)
            ->limit($limit)
            ->get();

        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => get_class($item),
                'name' => $item->name ?? $item->title ?? 'Unknown',
                'embedding' => $this->getItemEmbedding([
                    'category_id' => $item->category_id,
                    'price' => $item->price ?? 0,
                    'tags' => $item->tags ?? [],
                ]),
            ];
        });
    }

    /**
     * Get single item embedding
     */
    private function getItemEmbedding(array $features): array
    {
        try {
            $response = $this->http->timeout(1)->post(
                config('personalization.embedding_endpoint', 'http://localhost:8001/embed/item'),
                ['features' => $features]
            );

            if ($response->successful()) {
                return $response->json('embedding', []);
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return $this->generateRuleBasedItemEmbedding($features);
    }

    /**
     * Calculate cosine similarity between user and items
     *
     * @param array<float> $userEmbedding
     * @param Collection<int, array{id: int, embedding: array, type: string, name: string}> $itemEmbeddings
     * @return Collection<int, mixed>
     */
    private function calculateSimilarityScores(array $userEmbedding, Collection $itemEmbeddings): Collection
    {
        return $itemEmbeddings->map(function ($item) use ($userEmbedding) {
            $similarity = $this->cosineSimilarity($userEmbedding, $item['embedding']);

            return (object) [
                'id' => $item['id'],
                'type' => $item['type'],
                'name' => $item['name'],
                'similarity_score' => $similarity,
                'confidence' => min(0.9, $similarity + 0.1),
            ];
        })->sortByDesc('similarity_score')->values();
    }

    /**
     * Apply diversity filter to avoid similar items
     *
     * @param Collection<int, mixed> $candidates
     * @return Collection<int, mixed>
     */
    private function applyDiversityFilter(Collection $candidates, int $limit): Collection
    {
        $diverse = collect();
        $selectedCategories = [];

        foreach ($candidates as $candidate) {
            if ($diverse->count() >= $limit) {
                break;
            }

            // Get category (simplified)
            $category = $this->getItemCategory($candidate);

            // Limit items per category for diversity
            if (!isset($selectedCategories[$category]) || $selectedCategories[$category] < 2) {
                $diverse->push($candidate);
                $selectedCategories[$category] = ($selectedCategories[$category] ?? 0) + 1;
            }
        }

        // If we didn't get enough items, fill from remaining
        if ($diverse->count() < $limit) {
            $remaining = $candidates->diff($diverse)->take($limit - $diverse->count());
            $diverse = $diverse->concat($remaining);
        }

        return $diverse;
    }

    /**
     * Calculate cosine similarity between two vectors
     *
     * @param array<float> $vec1
     * @param array<float> $vec2
     */
    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        $len = min(count($vec1), count($vec2));

        for ($i = 0; $i < $len; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }

        if ($norm1 === 0 || $norm2 === 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }

    /**
     * Generate rule-based user embedding (fallback)
     */
    private function generateRuleBasedUserEmbedding(array $features): array
    {
        $embedding = array_fill(0, self::EMBEDDING_DIMENSION, 0.0);

        // Demographic features
        if (isset($features['demographic']['age'])) {
            $ageIndex = min(99, (int) $features['demographic']['age']);
            $embedding[$ageIndex % self::EMBEDDING_DIMENSION] += 0.3;
        }

        // Behavioral features
        if (isset($features['behavioral']['visits_last_30_days'])) {
            $visits = $features['behavioral']['visits_last_30_days'];
            $embedding[0] += min(1.0, $visits / 10) * 0.4;
        }

        // Preference features
        if (isset($features['preferences']['favorite_categories'])) {
            foreach ($features['preferences']['favorite_categories'] as $catId) {
                $index = $catId % self::EMBEDDING_DIMENSION;
                $embedding[$index] += 0.2;
            }
        }

        // Normalize
        $maxVal = max(abs($embedding));
        if ($maxVal > 0) {
            $embedding = array_map(fn ($v) => $v / $maxVal, $embedding);
        }

        return $embedding;
    }

    /**
     * Generate rule-based item embedding (fallback)
     */
    private function generateRuleBasedItemEmbedding(array $features): array
    {
        $embedding = array_fill(0, self::EMBEDDING_DIMENSION, 0.0);

        // Category
        if (isset($features['category_id'])) {
            $index = $features['category_id'] % self::EMBEDDING_DIMENSION;
            $embedding[$index] += 0.5;
        }

        // Price bucket
        if (isset($features['price'])) {
            $priceBucket = (int) ($features['price'] / 1000);
            $index = $priceBucket % self::EMBEDDING_DIMENSION;
            $embedding[$index] += 0.3;
        }

        // Tags
        if (isset($features['tags'])) {
            foreach ($features['tags'] as $tag) {
                $index = crc32($tag) % self::EMBEDDING_DIMENSION;
                $embedding[abs($index)] += 0.1;
            }
        }

        // Normalize
        $maxVal = max(abs($embedding));
        if ($maxVal > 0) {
            $embedding = array_map(fn ($v) => $v / $maxVal, $embedding);
        }

        return $embedding;
    }

    private function getItemCategory(object $candidate): string
    {
        return $this->db->table('categories')
            ->where('id', $candidate->category_id ?? 0)
            ->value('name') ?? 'other';
    }

    private function getEntityTypeForVertical(string $vertical): string
    {
        return match ($vertical) {
            'beauty' => \Modules\BeautyMasters\Domain\Entities\Service::class,
            'grooming' => \Modules\VetGrooming\Domain\Entities\Service::class,
            'food' => \Modules\Restaurant\Domain\Entities\Product::class,
            'fitness' => \Modules\Fitness\Domain\Entities\Service::class,
            default => throw new \InvalidArgumentException("Unknown vertical: {$vertical}"),
        };
    }
}
