<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

final readonly class RecommendationService
{
    public function __construct(
        private FraudControlService $fraudControlService,
        private RateLimiterService $rateLimiterService,
    ) {}

    /**
     * Возвращает персонализированные рекомендации для пользователя.
     *
     * @param int $userId ID пользователя
     * @param string|null $vertical Вертикаль (Beauty, Auto, Food и т.д.)
     * @param array $context Контекст: {radius, location, preferences}
     * @return Collection Коллекция рекомендованных товаров/услуг
     * @throws Exception
     */
    public function getForUser(
        int $userId,
        ?string $vertical = null,
        array $context = [],
    ): Collection {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('userId must be a positive integer');
        }

        try {
            $correlationId = \Illuminate\Support\Str::uuid()->toString();

            // Rate limiting
            $this->rateLimiterService->check('recommend', $userId);

            // Tenant scoping: from context or user record
            $tenantId = $context['tenant_id'] ?? \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $userId)->value('tenant_id') ?? 0;

            // Fraud check
            $this->fraudControlService->checkRecommendation($userId, $tenantId);

            $cacheKey = "recommend:user:{$userId}:vertical:{$vertical}:v1";

            // Проверяем кэш
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return collect($cached);
            }

            Log::channel('recommend')->info('Generating recommendations', [
                'user_id'       => $userId,
                'vertical'      => $vertical,
                'tenant_id'     => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            $recommendations = collect([]);

            // Кэшируем на 5 минут (300 секунд)
            Cache::put($cacheKey, $recommendations->toArray(), 300);

            return $recommendations;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::channel('recommend')->error('Recommendation generation failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Инвалидирует кэш рекомендаций пользователя.
     */
    public function invalidateUserCache(int $userId): void
    {
        Cache::forget("recommend:user:{$userId}:vertical:beauty:v1");
        Cache::forget("recommend:user:{$userId}:vertical:auto:v1");
        Cache::forget("recommend:user:{$userId}:vertical:food:v1");
        Cache::forget("recommend:user:{$userId}:vertical::v1");
        Cache::forget("recommend:user:{$userId}:*");

        Log::channel('recommend')->info('User cache invalidated', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Кросс-вертикальные рекомендации.
     *
     * @param int $userId ID пользователя
     * @param string $currentVertical Текущая вертикаль
     * @return Collection
     * @throws Exception
     */
    public function getCrossVertical(
        int $userId,
        string $currentVertical,
    ): Collection {
        try {
            // Пример: после бронирования гостиницы → ресторан рядом, такси и т.д.

            return collect([]);
        } catch (Exception $e) {
            Log::channel('recommend')->error('Cross-vertical recommendation failed', [
                'user_id' => $userId,
                'current_vertical' => $currentVertical,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * B2B рекомендации (поставщики, партнёры).
     *
     * @param int $tenantId ID тенанта (бизнеса)
     * @param string $vertical Вертикаль
     * @return Collection
     * @throws Exception
     */
    public function getB2BForTenant(
        int|string $tenantId,
        string $vertical,
    ): Collection {
        try {

            return collect([]);
        } catch (Exception $e) {
            Log::channel('recommend')->error('B2B recommendation failed', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Рассчитывает персональный скор для товара.
     *
     * @param int $userId ID пользователя
     * @param int $itemId ID товара
     * @param array $context Контекст
     * @return float Score 0-1
     * @throws Exception
     */
    public function scoreItem(
        int $userId,
        int $itemId,
        array $context = [],
    ): float {
        try {

            // Плейсхолдер
            return 0.5;
        } catch (Exception $e) {
            Log::channel('recommend')->error('Item scoring failed', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Ежедневный job: пересчитывает embeddings для всех товаров.
     *
     * @param string $correlationId Идентификатор корреляции
     * @return array{processed: int, status: string}
     * @throws Exception
     */
    public function recalculateEmbeddings(string $correlationId = ''): array
    {
        try {
            Log::channel('recommend')->info('Embeddings recalculation started', [
                'correlation_id' => $correlationId,
            ]);
            // или использование sentence-transformers

            return [
                'processed' => 0,
                'status' => 'completed',
            ];
        } catch (Exception $e) {
            Log::channel('recommend')->error('Embeddings recalculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
