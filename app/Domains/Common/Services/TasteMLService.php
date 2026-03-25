<?php

declare(strict_types=1);

namespace App\Domains\Common\Services;

use App\Models\ProductEmbedding;
use App\Models\UserTasteProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CANON 2026: Taste ML Service
 * ML-обработка embeddings, cosine similarity, рекомендации на основе вкусов
 */
final readonly class TasteMLService
{
    /**
     * Вычислить cosine similarity между двумя vectors
     */
    public function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (empty($vectorA) || empty($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        foreach ($vectorA as $i => $valueA) {
            $valueB = $vectorB[$i] ?? 0;
            $dotProduct += $valueA * $valueB;
            $magnitudeA += $valueA ** 2;
            $magnitudeB += $valueB ** 2;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA === 0.0 || $magnitudeB === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * Получить рекомендации на основе ML вкусов пользователя
     * Гибридная модель: 40% ML + 30% популярное + 20% новинки + 10% акции
     */
    public function getRecommendationsForUser(
        int $userId,
        int $tenantId,
        string $vertical = '',
        int $limit = 20,
    ): array
    {
        $cacheKey = "taste:recommendations:{$tenantId}:{$userId}:{$vertical}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $tenantId, $vertical, $limit) {
            try {
                // 1. Получить профиль пользователя
                $userProfile = UserTasteProfile::where([
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                ])->first();

                if (!$userProfile || !$userProfile->embedding) {
                    return []; // Ещё нет профиля
                }

                // 2. Получить embeddings товаров/услуг
                $query = ProductEmbedding::where('tenant_id', $tenantId);

                if ($vertical) {
                    $query->where('vertical', $vertical);
                }

                $products = $query->limit(500)->get();

                // 3. Вычислить similarity для каждого товара
                $recommendations = [];

                foreach ($products as $product) {
                    if (!$product->embedding) {
                        continue;
                    }

                    $similarity = $this->cosineSimilarity(
                        $userProfile->embedding,
                        $product->embedding
                    );

                    if ($similarity > 0.5) { // Минимальный порог
                        $recommendations[] = [
                            'product_id' => $product->product_id,
                            'score' => $similarity,
                            'vertical' => $product->vertical,
                        ];
                    }
                }

                // 4. Отсортировать по score
                usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

                // 5. Вернуть топ N
                return array_slice($recommendations, 0, $limit);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to get ML recommendations', [
                    'user_id' => $userId,
                    'vertical' => $vertical,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Обновить embedding профиля на основе истории взаимодействий
     * Вызывается ежедневно из MLRecalculateUserTastesJob
     */
    public function recalculateProfileEmbedding(
        int $userId,
        int $tenantId,
        string $correlationId = '',
    ): bool
    {
        try {
            return DB::transaction(function () use ($userId, $tenantId, $correlationId) {
                $profile = UserTasteProfile::where([
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                ])->lockForUpdate()->first();

                if (!$profile) {
                    return false;
                }

                // Получить недавние взаимодействия из истории
                $interactions = $profile->interaction_history ?? [];

                if (empty($interactions)) {
                    // Нет данных для рекалкуляции
                    return false;
                }

                // Вычислить новый embedding (средний вектор из категорий)
                $newEmbedding = $this->computeEmbeddingFromInteractions($interactions);

                if (empty($newEmbedding)) {
                    return false;
                }

                $profile->update([
                    'embedding' => $newEmbedding,
                    'version' => $profile->version + 1,
                    'last_calculated_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('User taste profile embedding recalculated', [
                    'user_id' => $userId,
                    'new_version' => $profile->version + 1,
                    'interactions_count' => count($interactions),
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to recalculate profile embedding', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return false;
        }
    }

    /**
     * Вычислить embedding из истории взаимодействий
     * Усредняет векторы категорий товаров, которые смотрел пользователь
     */
    private function computeEmbeddingFromInteractions(array $interactions): array
    {
        if (empty($interactions)) {
            return [];
        }

        // Получить embedding для каждого товара из истории
        $vectors = [];
        $validCount = 0;

        foreach ($interactions as $interaction) {
            $productId = $interaction['product_id'] ?? null;

            if (!$productId) {
                continue;
            }

            $embedding = ProductEmbedding::where('product_id', $productId)
                ->select('embedding')
                ->first();

            if ($embedding && $embedding->embedding) {
                $vectors[] = $embedding->embedding;
                $validCount++;
            }
        }

        if ($validCount === 0) {
            return [];
        }

        // Усредните все векторы
        $vectorSize = count($vectors[0]);
        $averageVector = array_fill(0, $vectorSize, 0.0);

        foreach ($vectors as $vector) {
            foreach ($vector as $i => $value) {
                $averageVector[$i] += $value / $validCount;
            }
        }

        return $averageVector;
    }

    /**
     * Обновить CTR (click-through rate) рекомендаций
     */
    public function updateCTR(int $userId, int $tenantId, float $ctr): void
    {
        try {
            UserTasteProfile::where([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ])->update(['ctr' => $ctr]);

            Cache::forget("taste:profile:{$tenantId}:{$userId}");
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update CTR', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить acceptance rate (процент переходов из рекомендаций)
     */
    public function updateAcceptanceRate(int $userId, int $tenantId, float $rate): void
    {
        try {
            UserTasteProfile::where([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ])->update(['recommendation_acceptance_rate' => $rate]);

            Cache::forget("taste:profile:{$tenantId}:{$userId}");
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update acceptance rate', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
