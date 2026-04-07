<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Http\Request;
use App\Models\ProductEmbedding;
use App\Models\UserTasteProfile;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class TasteMLService
{
    public function __construct(
        private readonly Request $request,
            private readonly \OpenAI\Client $openai,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
    ) {}

        /**
         * Получить рекомендации на основе профиля вкуса
         * Использует гибридный подход: ML (40%) + популярное (30%) + новинки (20%) + акции (10%)
         */
        public function getRecommendationsByTaste(
            UserTasteProfile $profile,
            ?string $vertical = null,
            int $limit = 20
        ): array {
            try {
                if (!$profile->embedding || $profile->analysis_status !== 'processed') {
                    return [];
                }

                // Получить компоненты рекомендаций
                $mlRecs = $this->getMLRecommendations($profile, $vertical, (int)($limit * 0.4));
                $popularRecs = $this->getPopularRecommendations($vertical, (int)($limit * 0.3));
                $newRecs = $this->getNewRecommendations($vertical, (int)($limit * 0.2));
                $promoRecs = $this->getPromoRecommendations($vertical, (int)($limit * 0.1));

                // Объединить и выбрать топ
                $allRecs = \array_merge($mlRecs, $popularRecs, $newRecs, $promoRecs);

                // Дедупликировать и сортировать
                $seen = [];
                $result = [];

                foreach ($allRecs as $rec) {
                    $key = "{$rec['type']}:{$rec['id']}";
                    if (!isset($seen[$key])) {
                        $result[] = $rec;
                        $seen[$key] = true;
                    }

                    if (\count($result) >= $limit) {
                        break;
                    }
                }

                // Логировать решение
                $this->logMLDecision($profile->user_id, 'show_recommendation', [
                    'vertical' => $vertical,
                    'limit' => $limit,
                    'components' => [
                        'ml' => \count($mlRecs),
                        'popular' => \count($popularRecs),
                        'new' => \count($newRecs),
                        'promo' => \count($promoRecs),
                    ],
                ], [
                    'recommendation_count' => \count($result),
                ]);

                return $result;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get recommendations by taste', [
                    'user_id' => $profile->user_id,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        }

        /**
         * Получить ML-рекомендации на основе cosine similarity
         * Сравниваем embedding пользователя с embeddings товаров
         */
        private function getMLRecommendations(
            UserTasteProfile $profile,
            ?string $vertical = null,
            int $limit = 10
        ): array {
            try {
                $userEmbedding = $profile->embedding;

                if (!$userEmbedding) {
                    return [];
                }

                // Получить embeddings товаров (в реальности это должна быть векторная БД, но тут используем JSON)
                $query = ProductEmbedding::where('model_version', '=', $profile->ml_version);

                if ($vertical) {
                    $query->where('product_metadata->vertical', '=', $vertical);
                }

                $embeddings = $query->limit(500)->get();

                // Рассчитать cosine similarity для каждого товара
                $similarities = [];

                foreach ($embeddings as $embedding) {
                    $similarity = $embedding->cosineSimilarity($userEmbedding);

                    if ($similarity > 0.5) {  // Только если similarity > 0.5
                        $similarities[] = [
                            'id' => $embedding->embeddable_id,
                            'type' => $embedding->embeddable_type,
                            'similarity' => $similarity,
                            'metadata' => $embedding->product_metadata,
                        ];
                    }
                }

                // Сортировать по similarity (убыванию)
                \usort($similarities, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

                // Вернуть топ-N
                return \array_slice($similarities, 0, $limit);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get ML recommendations', ['error' => $e->getMessage()]);

                return [];
            }
        }

        /**
         * Получить популярные товары (для разнообразия)
         */
        private function getPopularRecommendations(?string $vertical = null, int $limit = 10): array
        {
            // В реальности это должен быть запрос к реальным товарам
            // Здесь упрощённо: берём товары с высокими рейтингами

            return [];
        }

        /**
         * Получить новые товары/услуги
         */
        private function getNewRecommendations(?string $vertical = null, int $limit = 10): array
        {
            // Товары, добавленные за последние 7 дней

            return [];
        }

        /**
         * Получить товары на акции
         */
        private function getPromoRecommendations(?string $vertical = null, int $limit = 10): array
        {
            // Товары с активными промо-кампаниями

            return [];
        }

        /**
         * Генерировать embedding для товара/услуги
         */
        public function generateProductEmbedding(
            string $type,
            int $id,
            string $text,
            array $metadata = []
        ): ?array {
            try {
                $response = $this->openai->embeddings()->create([
                    'model' => 'text-embedding-3-small',
                    'input' => $text,
                ]);

                $embedding = $response->embeddings[0]->embedding ?? null;

                if (!$embedding) {
                    throw new \DomainException('Operation returned no result');
                }

                // Сохранить embedding в БД
                ProductEmbedding::updateOrCreate(
                    [
                        'embeddable_type' => $type,
                        'embeddable_id' => $id,
                        'tenant_id' => tenant()->id,
                    ],
                    [
                        'embedding' => $embedding,
                        'source_text' => $text,
                        'model_version' => 1,
                        'product_metadata' => $metadata,
                    ]
                );

                return $embedding;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to generate product embedding', [
                    'type' => $type,
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]);

                throw new \RuntimeException('Unable to generate product embedding.', previous: $e);
            }
        }

        /**
         * Логировать ML-решение (для аудита и оптимизации)
         */
        public function logMLDecision(
            int $userId,
            string $decisionType,
            array $context,
            array $mlOutput
        ): void {
            try {
                $this->db->table('ml_decision_logs')->insert([
                    'user_id' => $userId,
                    'tenant_id' => tenant()->id,
                    'decision_type' => $decisionType,
                    'context' => \json_encode($context),
                    'ml_features' => \json_encode([]),
                    'ml_output' => \json_encode($mlOutput),
                    'final_decision' => \json_encode($mlOutput),
                    'confidence_score' => 0.0,
                    'ml_version' => 1,
                    'correlation_id' => $this->request->header('X-Correlation-ID'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to log ML decision', ['error' => $e->getMessage()]);
            }
        }

        /**
         * Получить похожие товары (для карточки товара)
         */
        public function getSimilarProducts(
            string $productType,
            int $productId,
            int $limit = 10
        ): array {
            try {
                $productEmbedding = ProductEmbedding::where('embeddable_type', '=', $productType)
                    ->where('embeddable_id', '=', $productId)
                    ->first();

                if (!$productEmbedding) {
                    return [];
                }

                $userEmbedding = $productEmbedding->getEmbeddingArray();
                $similarities = [];

                // Получить другие товары этой же вертикали
                $otherProducts = ProductEmbedding::where('embeddable_type', '=', $productType)
                    ->where('embeddable_id', '!=', $productId)
                    ->limit(100)
                    ->get();

                foreach ($otherProducts as $other) {
                    $similarity = $other->cosineSimilarity($userEmbedding);

                    if ($similarity > 0.6) {
                        $similarities[] = [
                            'id' => $other->embeddable_id,
                            'type' => $other->embeddable_type,
                            'similarity' => $similarity,
                        ];
                    }
                }

                \usort($similarities, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

                return \array_slice($similarities, 0, $limit);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get similar products', ['error' => $e->getMessage()]);

                return [];
            }
        }

        /**
         * Обновить статистику рекомендаций
         */
        public function recordRecommendationClick(int $userId, int $recommendedProductId): void
        {
            $profile = UserTasteProfile::where('user_id', '=', $userId)->first();

            if ($profile) {
                $profile->recordRecommendationView(clicked: true);

                $this->logger->channel('audit')->info('Recommendation clicked', [
                    'user_id' => $userId,
                    'product_id' => $recommendedProductId,
                    'ctr' => $profile->recommendation_ctr,
                ]);
            }
        }
}
