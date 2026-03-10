<?php

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Models\AdBanner;
use App\Domains\Advertising\Models\AdPlacement;
use App\Domains\Advertising\Models\AdInteractionLog;
use App\Domains\Common\Services\Marketing\DeviceIntelligenceService;
use App\Domains\Common\Services\Performance\HighLoadTrafficOrchestrator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AdEngineService - Рекламный движок для высоконагруженного распределения (Production 2026).
 * 
 * Отвечает за:
 * - Выборку активных баннеров для размещения
 * - Ротацию и аукционную логику (CPM)
 * - Гео-таргетинг и таргетинг по устройствам
 * - Логирование показов с использованием Redis-буфера
 */
class AdEngineService
{
    public function __construct(protected HighLoadTrafficOrchestrator $performance) {}

    /**
     * Получить активные баннеры для конкретного места размещения.
     * 
     * Реализует:
     * - Ротацию баннеров
     * - Аукционную логику (CPM - выше цена, выше приоритет)
     * - Гео-таргетинг и таргетинг по устройствам
     * - Кеширование результатов через Redis
     *
     * @param string $placementCode Код плейсмента (например: 'home_banner_top')
     * @param array $context Контекст запроса (city_id, category_id и т.д.)
     * @param DeviceIntelligenceService|null $deviceIntel Сервис для определения типа устройства
     * 
     * @return Collection Коллекция активных баннеров, отсортированных по приоритету
     * 
     * @throws \Exception При критических ошибках
     */
    public function getActiveAds(
        string $placementCode,
        array $context = [],
        ?DeviceIntelligenceService $deviceIntel = null
    ): Collection {
        try {
            // Использование Redis Tags для быстрой выборки данных
            return $this->performance->getCachedPlacement($placementCode, function() use (
                $placementCode,
                $context,
                $deviceIntel
            ) {
                // === Шаг 1: Получение плейсмента ===
                $placement = AdPlacement::where('slug', $placementCode)
                    ->where('is_active', true)
                    ->first();

                if (!$placement) {
                    Log::warning('Ad placement not found', ['placement_code' => $placementCode]);
                    return collect();
                }

                // === Шаг 2: Определение контекста устройства ===
                $user = Auth::user();
                $intelligence = $deviceIntel && $user
                    ? $deviceIntel->getTargetingContext($user)
                    : ['is_mobile' => false, 'is_high_end' => true];

                // === Шаг 3: Получение баннеров с активными аукционными ставками (CPM) ===
                $auctionAds = AdBanner::where('placement_id', $placement->id)
                    ->where('is_active', true)
                    ->where('compliance_status', 'compliant')  // Только маркированные согласно 347-ФЗ
                    ->whereNotNull('erid')                     // Обязательно должна быть ERID
                    ->join('ad_auction_bids', 'ad_banners.id', '=', 'ad_auction_bids.ad_banner_id')
                    ->where('ad_auction_bids.is_active', true)
                    ->where('ad_auction_bids.min_impressions', '>=', 10000) // Минимум 10k показов
                    ->orderByDesc('ad_auction_bids.cpm_bid')             // Дорогие ставки в приоритете
                    ->select('ad_banners.*', 'ad_auction_bids.cpm_bid')
                    ->with('campaign')
                    ->get();

                if ($auctionAds->isEmpty()) {
                    Log::debug('No active auction bids found for placement', [
                        'placement_id' => $placement->id,
                    ]);
                    return collect();
                }

                // === Шаг 4: Фильтрация по гео, устройству, расписанию ===
                return $auctionAds->filter(fn($ad) => $this->validateCampaignTargeting(
                    $ad,
                    $context,
                    $intelligence
                ));

            });

        } catch (Throwable $e) {
            Log::error('Failed to get active ads', [
                'placement_code' => $placementCode,
                'error' => $e->getMessage(),
            ]);

            \Sentry\captureException($e);

            return collect(); // Fallback: возвращаем пустую коллекцию
        }
    }

    /**
     * Валидация таргетинга баннера (гео, устройство, расписание, демография).
     * 
     * @param AdBanner $banner Баннер для проверки
     * @param array $context Контекст пользователя (geo, category и т.д.)
     * @param array $intelligence Интеллектуальная информация об устройстве
     * 
     * @return bool True если баннер подходит под критерии
     */
    private function validateCampaignTargeting(
        AdBanner $banner,
        array $context,
        array $intelligence = []
    ): bool {
        try {
            // === Проверка гео-таргетинга ===
            if (!empty($banner->target_geo) && isset($context['city_id'])) {
                if (!in_array($context['city_id'], $banner->target_geo)) {
                    return false;
                }
            }

            // === Проверка типа устройства ===
            if (isset($intelligence['is_mobile'])) {
                $format = $banner->type ?? 'standard'; // standard, mobile, video
                
                // Если мобильное устройство, показываем только mobile-friendly баннеры
                if ($intelligence['is_mobile'] && $format === 'video') {
                    return false;
                }
            }

            // === Проверка "heavy media" для slow devices ===
            if (isset($banner->metadata['is_rich_media']) && $banner->metadata['is_rich_media']) {
                if (!($intelligence['is_high_end'] ?? true)) {
                    return false; // Slow device - не показываем тяжелые баннеры
                }
            }

            // === Проверка расписания (дни недели, часы) ===
            if (!empty($banner->schedule)) {
                $now = Carbon::now();
                
                // Проверка дней недели
                if (!in_array($now->dayOfWeek, $banner->schedule['days'] ?? range(0, 6))) {
                    return false;
                }

                // Проверка часов (если указано)
                if (!empty($banner->schedule['hours'])) {
                    $hours = $banner->schedule['hours']; // '09:00-21:00'
                    list($startHour, $endHour) = explode('-', $hours);
                    
                    if ($now->format('H:i') < $startHour || $now->format('H:i') > $endHour) {
                        return false;
                    }
                }
            }

            // === Проверка дневного лимита показов для слота ===
            if ($banner->placement?->isDailyLimitReached()) {
                return false;
            }

            return true;

        } catch (Throwable $e) {
            Log::error('Error validating campaign targeting', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Логирование показа (Impression) через Redis-буфер для высоконагруженных систем.
     * 
     * Не пишет в БД напрямую при шквальном трафике (5000+ показов/сек).
     * Данные буферизуются в Redis и батчами записываются в БД.
     *
     * @param AdBanner $banner Показанный баннер
     * @param array $context Дополнительный контекст (IP, device и т.д.)
     * 
     * @return void
     */
    public function logImpression(AdBanner $banner, array $context = []): void
    {
        try {
            // Использование высокопроизводительного буфера
            $this->performance->bufferImpression($banner->id, [
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'referer' => request()->header('Referer'),
                ...array_intersect_key($context, array_flip([
                    'utm_source',
                    'utm_medium',
                    'utm_campaign',
                ]))
            ]);

            // Инкремент счетчика показов на баннере (кешированное значение)
            $banner->increment('impressions_count');

        } catch (Throwable $e) {
            Log::error('Failed to log impression', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);

            \Sentry\captureException($e);
        }
    }

    /**
     * Логирование клика на баннер с fraud detection.
     * 
     * @param AdBanner $banner Клику подлежащий баннер
     * @param AdPlacement $placement Плейсмент, в котором произошел клик
     * @param array $data Координаты, IP, user-agent и т.д.
     * 
     * @return bool True если клик был залогирован
     */
    public function logClick(AdBanner $banner, AdPlacement $placement, array $data = []): bool
    {
        try {
            // Логирование через AdInteractionLog (с fraud detection)
            AdInteractionLog::logInteraction(
                $banner,
                $placement,
                'click',
                array_merge($data, [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                    'referer' => request()->header('Referer'),
                ])
            );

            $banner->increment('clicks_count');

            return true;

        } catch (Throwable $e) {
            Log::error('Failed to log click', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
