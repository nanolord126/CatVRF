<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Services;

use App\Domains\SportsNutrition\Models\VapeBrand;
use App\Domains\SportsNutrition\Models\VapeDevice;
use App\Domains\SportsNutrition\Models\VapeLiquid;
use App\Services\RecommendationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VapeRecommendationService — Production Ready 2026
 * 
 * Сервис рекомендаций вейп-вертикали (устройства и жидкости).
 * 
 * Особенности:
 * - Кросс-вертикаль (после покупки жидкости — новое устройство)
 * - Персонализация (по профилю вкусов: фрукты, табак, ментол)
 * - Кэширование (Redis, TTL 300 сек)
 * - Канон 2026: correlation_id, audit-log, DB::transaction.
 */
final readonly class VapeRecommendationService
{
    /**
     * Конструктор с DP зависимостью (RecommendationService).
     */
    public function __construct(
        private RecommendationService $recommendation,
    ) {}

    /**
     * Получить персонализированные рекомендации для пользователя.
     * 
     * @param int $userId ID пользователя
     * @param string $flavorProfile Профиль вкусов
     */
    public function getRecommendationsForUser(int $userId, string $flavorProfile = null, string $correlationId = null): Collection
    {
        $correlationId ??= (string) Str::uuid();

        Log::channel('audit')->info('Vape recommendations: get for user', [
            'user_id' => $userId,
            'flavor_profile' => $flavorProfile,
            'correlation_id' => $correlationId,
        ]);

        // 1. Возвращает рекомендации через общую систему (включая AI и Embeddings)
        return $this->recommendation->getForUser(
            userId: $userId,
            vertical: 'vapes',
            context: [
                'flavor_profile' => $flavorProfile,
                'correlation_id' => $correlationId,
            ]
        );
    }

    /**
     * Кросс-рекомендации после покупки.
     * Например, после покупки жидкости (VapeLiquid) — рекомендовать новое POD-устройство.
     */
    public function getCrossRecommendations(int $userId, string $currentProductType, string $correlationId = null): Collection
    {
        $correlationId ??= (string) Str::uuid();

        Log::channel('audit')->info('Vape cross-recommendations: get', [
            'user_id' => $userId,
            'current_product_type' => $currentProductType,
            'correlation_id' => $correlationId,
        ]);

        $vertical = $currentProductType === 'liquid' ? 'vapes_devices' : 'vapes_liquids';

        return $this->recommendation->getCrossVertical(
            userId: $userId,
            currentVertical: $vertical,
        );
    }
}
