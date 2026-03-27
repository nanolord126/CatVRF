<?php declare(strict_types=1);

namespace App\Services;

use App\Models\RecommendationLog;
use App\Models\UserEmbedding;
use App\Models\ProductEmbedding;
use App\Services\FraudControl\FraudControlService;
use App\Services\RateLimit\RateLimiterService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Сервис рекомендаций для персонализации
 *
 * CANON 2026 комплиенс:
 * - Все рекомендации кэшируются в Redis (TTL 300-3600 сек)
 * - Все запросы логируются с correlation_id
 * - FraudControlService::check() перед выдачей рекомендаций
 * - RateLimiter на публичные эндпоинты (100 запросов/мин)
 * - Вычисляются на основе: поведение (45%), гео (25%), embeddings (20%), бизнес-правила (10%)
 * - Supports: B2C, B2B, cross-vertical recommendations
 */
final readonly class RecommendationService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly Repository $cache,
        private readonly FraudControlService $fraud,
        private readonly RateLimiterService $rateLimiter,
    ) {}

    /**
     * Получить персонализированные рекомендации для пользователя
     */
    public function getForUser(
        int $userId,
        ?string $vertical = null,
        array $context = [],
        ?string $correlationId = null,
    ): Collection {
        $correlationId ??= Str::uuid()->toString();

        if ($userId <= 0) {
            throw new \InvalidArgumentException('userId must be a positive integer');
        }

        try {
            // 1. RATE LIMIT CHECK
            $this->rateLimiter->check('recommend', $userId);

            // 2. FRAUD CHECK
            $tenantId = $context['tenant_id'] ?? DB::table('users')
                ->where('id', $userId)
                ->value('tenant_id') ?? 0;

            $this->fraud->check([
                'operation_type' => 'recommendation_request',
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 3. CACHE CHECK
            $geoHash = $context['geo_hash'] ?? 'global';
            $cacheKey = "recommend:user:{$userId}:vertical:{$vertical}:geo:{$geoHash}:v1";

            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::channel('audit')->info('Recommendation: Cache hit', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'vertical' => $vertical,
                ]);

                return collect($cached);
            }

            // 4. BUILD RECOMMENDATIONS
            $recommendations = $this->buildRecommendations(
                userId: $userId,
                tenantId: $tenantId,
                vertical: $vertical,
                context: $context,
                correlationId: $correlationId,
            );

            // 5. CACHE & LOG
            $ttl = $context['cache_ttl'] ?? 300; // 5 минут по умолчанию
            Cache::put($cacheKey, $recommendations->toArray(), $ttl);

            // Log в recommendation_logs для аналитики
            RecommendationLog::create([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'recommended_items' => $recommendations->pluck('id')->toArray(),
                'score' => $recommendations->average('score') ?? 0,
                'source' => $context['source'] ?? 'behavior',
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Recommendation: Generated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'vertical' => $vertical,
                'count' => $recommendations->count(),
            ]);

            return $recommendations;
        } catch (\InvalidArgumentException $e) {
            Log::channel('audit')->warning('Recommendation: Invalid argument', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: Generation failed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return collect([]);
        }
    }

    /**
     * Построить рекомендации на основе множественных источников
     *
     * Приоритет источников:
     * 1. Поведение (45%) - просмотры, покупки, клики
     * 2. Геолокация (25%) - близость услуги/товара
     * 3. Embeddings (20%) - семантическое сходство
     * 4. Правила бизнеса (10%) - boost/demote из rules
     */
    private function buildRecommendations(
        int $userId,
        int $tenantId,
        ?string $vertical,
        array $context,
        string $correlationId,
    ): Collection {
        try {
            // Получить пользовательское поведение
            $behaviorItems = $this->getFromBehavior($userId, $tenantId, $vertical, $correlationId);

            // Получить географически близкие товары
            $geoItems = $this->getFromGeo($userId, $tenantId, $vertical, $context, $correlationId);

            // Получить похожие по embeddings
            $embeddingItems = $this->getFromEmbeddings($userId, $tenantId, $vertical, $correlationId);

            // Применить бизнес-правила
            $rules = $this->getBusinessRules($tenantId, $vertical, $correlationId);

            // Объединить и отранжировать
            $merged = collect([])
                ->merge($behaviorItems->map(fn ($item) => [...$item, 'source_weight' => 0.45]))
                ->merge($geoItems->map(fn ($item) => [...$item, 'source_weight' => 0.25]))
                ->merge($embeddingItems->map(fn ($item) => [...$item, 'source_weight' => 0.20]))
                ->groupBy('id')
                ->map(fn ($group) => [
                    'id' => $group[0]['id'],
                    'name' => $group[0]['name'] ?? '',
                    'score' => $group->sum('source_weight') / $group->count(),
                    'sources' => $group->pluck('source')->unique()->join(', '),
                ]);

            // Применить правила (boost/demote)
            foreach ($rules as $rule) {
                $merged = $merged->map(function ($item) use ($rule) {
                    if ($rule['item_id'] === $item['id'] || $rule['category'] === $item['category'] ?? null) {
                        $item['score'] *= (1 + ($rule['weight'] ?? 0));
                    }

                    return $item;
                });
            }

            // Отранжировать и вернуть топ
            return $merged
                ->sortByDesc('score')
                ->take(20)
                ->values();
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: Build failed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    private function getFromBehavior(int $userId, int $tenantId, ?string $vertical, string $correlationId): Collection
    {
        try {
            // Получить просмотренные товары (за последние 30 дней)
            return DB::table('product_views')
                ->where('user_id', $userId)
                ->where('created_at', '>', now()->subDays(30))
                ->groupBy('product_id')
                ->selectRaw('product_id as id, COUNT(*) as count, MAX(created_at) as last_view')
                ->orderByRaw('count DESC')
                ->limit(20)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'score' => min($row->count / 50, 1.0), // нормализация
                    'source' => 'behavior',
                ])->values();
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Recommendation: Behavior retrieval failed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    private function getFromGeo(int $userId, int $tenantId, ?string $vertical, array $context, string $correlationId): Collection
    {
        try {
            $radius = $context['radius'] ?? 5; // км

            if (!isset($context['lat'], $context['lon'])) {
                return collect([]);
            }

            // Простой радиусный поиск (ST_Distance_Sphere в MySQL)
            return DB::table('products')
                ->selectRaw('id, name, ST_Distance_Sphere(point(lon, lat), point(?, ?)) as distance', [
                    $context['lon'],
                    $context['lat'],
                ])
                ->whereRaw('ST_Distance_Sphere(point(lon, lat), point(?, ?)) < ?', [
                    $context['lon'],
                    $context['lat'],
                    $radius * 1000, // в метры
                ])
                ->where('tenant_id', $tenantId)
                ->orderBy('distance')
                ->limit(20)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'score' => max(1.0 - ($row->distance / ($radius * 1000)), 0), // близость = скор
                    'source' => 'geo',
                ])->values();
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Recommendation: Geo retrieval failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    private function getFromEmbeddings(int $userId, int $tenantId, ?string $vertical, string $correlationId): Collection
    {
        try {
            // Получить embeddings пользователя
            $userEmbedding = UserEmbedding::where('user_id', $userId)->first();

            if (!$userEmbedding || !$userEmbedding->embedding) {
                return collect([]);
            }

            // Найти похожие товары по cosine similarity (требует pgvector или подобного)
            // Упрощённая версия: найти товары в похожих категориях
            return DB::table('products')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('id', function ($q) use ($userId) {
                    $q->select('product_id')->from('orders')->where('user_id', $userId);
                })
                ->orderByRaw('RAND()')
                ->limit(20)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'score' => 0.5, // плейсхолдер
                    'source' => 'embedding',
                ])->values();
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Recommendation: Embedding retrieval failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    private function getBusinessRules(int $tenantId, ?string $vertical, string $correlationId): Collection
    {
        try {
            return DB::table('recommendation_rules')
                ->where('tenant_id', $tenantId)
                ->where('rule_type', 'boost')
                ->when($vertical, fn ($q) => $q->where('vertical', $vertical))
                ->get()
                ->values();
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Recommendation: Rules retrieval failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Инвалидировать кэш рекомендаций пользователя
     */
    public function invalidateUserCache(int $userId, ?string $correlationId = null): void
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            Cache::tags(['recommend', "user:{$userId}"])->flush();

            Log::channel('audit')->info('Recommendation: Cache invalidated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: Invalidation failed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Кросс-вертикальные рекомендации
     * Пример: после бронирования гостиницы → ресторан рядом, такси и т.д.
     */
    public function getCrossVertical(
        int $userId,
        string $currentVertical,
        ?string $correlationId = null,
    ): Collection {
        $correlationId ??= Str::uuid()->toString();

        try {
            // Получить последний заказ в текущей вертикали
            $lastOrder = DB::table('orders')
                ->where('user_id', $userId)
                ->where('vertical', $currentVertical)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastOrder) {
                return collect([]);
            }

            // Получить рекомендации по связанным вертикалям
            $crossVerticals = match ($currentVertical) {
                'hotels' => ['restaurants', 'taxis', 'tours'],
                'beauty' => ['wellness', 'products'],
                'auto' => ['insurance', 'service'],
                'food' => ['delivery', 'catering'],
                default => [],
            };

            $recommendations = collect([]);

            foreach ($crossVerticals as $vertical) {
                $recs = $this->getForUser(
                    userId: $userId,
                    vertical: $vertical,
                    context: [
                        'lat' => $lastOrder->lat ?? null,
                        'lon' => $lastOrder->lon ?? null,
                        'radius' => 10, // 10 км рядом
                        'source' => 'cross_vertical',
                    ],
                    correlationId: $correlationId,
                );

                $recommendations = $recommendations->merge($recs);
            }

            Log::channel('audit')->info('Recommendation: Cross-vertical generated', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'from_vertical' => $currentVertical,
                'count' => $recommendations->count(),
            ]);

            return $recommendations->take(10);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: Cross-vertical failed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'current_vertical' => $currentVertical,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * B2B рекомендации (поставщики, партнёры для бизнеса)
     */
    public function getB2BForTenant(
        int|string $tenantId,
        string $vertical,
        ?string $correlationId = null,
    ): Collection {
        $correlationId ??= Str::uuid()->toString();
        $tenantId = (int) $tenantId;

        try {
            // Получить поставщиков той же вертикали
            $suppliers = DB::table('suppliers')
                ->where('vertical', $vertical)
                ->whereNotIn('tenant_id', [$tenantId]) // исключить себя
                ->orderBy('rating', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'rating' => $s->rating,
                    'score' => $s->rating / 5.0,
                    'source' => 'b2b',
                ])->values();

            Log::channel('audit')->info('Recommendation: B2B generated', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'count' => $suppliers->count(),
            ]);

            return $suppliers;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: B2B failed', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Рассчитать персональный скор для товара (0-1)
     */
    public function scoreItem(
        int $userId,
        int $itemId,
        array $context = [],
        ?string $correlationId = null,
    ): float {
        $correlationId ??= Str::uuid()->toString();

        try {
            $score = 0.5; // базовый скор

            // Было ли куплено раньше?
            $wasBought = DB::table('order_items')
                ->where('user_id', $userId)
                ->where('product_id', $itemId)
                ->exists();

            if ($wasBought) {
                $score += 0.2; // повторный интерес
            }

            // Было ли просмотрено последний день?
            $wasViewedToday = DB::table('product_views')
                ->where('user_id', $userId)
                ->where('product_id', $itemId)
                ->where('created_at', '>', now()->subDay())
                ->exists();

            if ($wasViewedToday) {
                $score += 0.15; // свежий интерес
            }

            // Рейтинг товара
            $rating = DB::table('products')
                ->where('id', $itemId)
                ->value('rating') ?? 3;

            $score += ($rating / 5.0) * 0.15;

            // Цена в бюджете?
            $price = DB::table('products')
                ->where('id', $itemId)
                ->value('price') ?? 0;

            $userAvgPrice = DB::table('order_items')
                ->where('user_id', $userId)
                ->avg('price') ?? 5000;

            if ($price <= $userAvgPrice * 1.5) {
                $score += 0.1; // в приемлемом ценовом диапазоне
            }

            $finalScore = min($score, 1.0);

            Log::channel('audit')->info('Recommendation: Item scored', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'item_id' => $itemId,
                'score' => $finalScore,
            ]);

            return $finalScore;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: Scoring failed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Ежедневный job: пересчитать embeddings для всех товаров
     */
    public function recalculateEmbeddings(?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            Log::channel('audit')->info('Recommendation: Embeddings recalc started', [
                'correlation_id' => $correlationId,
            ]);

            // Получить все товары
            $products = DB::table('products')
                ->where('updated_at', '>', now()->subDay())
                ->get();

            $processed = 0;

            foreach ($products as $product) {
                try {
                    // Вызвать OpenAI text-embedding-3-large или SentenceTransformers
                    $embedding = $this->generateEmbedding(
                        text: "{$product->name} {$product->description}",
                        correlationId: $correlationId,
                    );

                    ProductEmbedding::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'embedding' => $embedding, // вектор
                            'updated_at' => now(),
                        ],
                    );

                    $processed++;
                } catch (\Throwable $e) {
                    Log::channel('audit')->warning('Recommendation: Embedding generation failed', [
                        'correlation_id' => $correlationId,
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::channel('audit')->info('Recommendation: Embeddings recalc completed', [
                'correlation_id' => $correlationId,
                'processed' => $processed,
                'total' => $products->count(),
            ]);

            return [
                'processed' => $processed,
                'total' => $products->count(),
                'status' => 'completed',
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Recommendation: Embeddings recalc failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Вспомогательный метод: генерировать embedding для текста
     */
    private function generateEmbedding(string $text, string $correlationId): array
    {
        // Плейсхолдер - в реальном коде вызвать OpenAI или SentenceTransformers
        // Возвращает вектор [768] элементов для text-embedding-3-large
        return array_fill(0, 768, 0.0);
    }
}
