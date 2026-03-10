<?php

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Models\AdCampaign;
use App\Domains\Advertising\Models\AdBanner;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * AdvertisingService - Главный сервис управления рекламой (Production 2026).
 * 
 * Отвечает за:
 * - Получение активных баннеров с фильтрацией по geo/vertical
 * - Трекинг кликов с аудит логированием
 * - Кеширование результатов для оптимизации
 */
class AdvertisingService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Получить активные баннеры с фильтрацией по гео и вертикали (Production 2026).
     *
     * @param string $geo Географический фильтр (регион/город)
     * @param string $vertical Вертиль маркетплейса (flowers, taxi, restaurants и т.д.)
     * @return array Активные кампании с баннерами
     */
    public function getActiveBanners(string $geo, string $vertical): array
    {
        try {
            if (empty($geo) || empty($vertical)) {
                throw new \InvalidArgumentException('geo и vertical параметры обязательны');
            }

            return Cache::remember("ads_{$geo}_{$vertical}", 3600, function () use ($geo, $vertical) {
                Log::debug('Fetching active banners', [
                    'geo' => $geo,
                    'vertical' => $vertical,
                    'correlation_id' => $this->correlationId,
                ]);

                return AdCampaign::where('is_active', true)
                    ->whereJsonContains('targeting_geo', $geo)
                    ->where('vertical', $vertical)
                    ->with('banners')
                    ->get()
                    ->toArray();
            });
        } catch (Throwable $e) {
            Log::error('Failed to fetch active banners', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);
            return [];
        }
    }

    /**
     * Трекинг клика по баннеру с аудит логированием (Production 2026).
     *
     * @param int $bannerId ID баннера
     * @return bool Успешность трекинга
     */
    public function trackClick(int $bannerId): bool
    {
        try {
            $banner = AdBanner::findOrFail($bannerId);

            $banner->increment('clicks');

            AuditLog::create([
                'action' => 'advertising.banner_click_tracked',
                'description' => "Клик по баннеру {$bannerId}",
                'model_type' => 'AdBanner',
                'model_id' => $bannerId,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'banner_id' => $bannerId,
                    'campaign_id' => $banner->campaign_id ?? null,
                ],
            ]);

            Log::info('Banner click tracked', [
                'banner_id' => $bannerId,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to track banner click', [
                'banner_id' => $bannerId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);
            return false;
        }
    }
}
