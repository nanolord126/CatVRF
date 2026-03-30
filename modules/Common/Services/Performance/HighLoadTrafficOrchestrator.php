<?php declare(strict_types=1);

namespace Modules\Common\Services\Performance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HighLoadTrafficOrchestrator extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlationId;
    
        public function __construct()
        {
            $this->correlationId = Str::uuid();
        }
    
        /**
         * Асинхронная запись просмотров рекламы через Redis-буфер.
         * Решает проблему дедлоков в БД при 5000+ запросах в минуту.
         */
        public function bufferImpression(int $bannerId): void
        {
            try {
                $key = "ad_impressions_buffer";
                Redis::hincrby($key, (string)$bannerId, 1);
                
                Log::channel('performance')->debug('HighLoadTrafficOrchestrator: impression buffered', [
                    'correlation_id' => $this->correlationId,
                    'banner_id' => $bannerId,
                ]);
    
                // Порог для автоматического сброса в БД (через 100 накопленных кликов)
                $current = Redis::hget($key, (string)$bannerId);
                if ($current && $current % 100 === 0) {
                    $this->flushImpressionsToDb($bannerId);
                }
            } catch (Throwable $e) {
                Log::error('HighLoadTrafficOrchestrator: impression buffering failed', [
                    'correlation_id' => $this->correlationId,
                    'banner_id' => $bannerId,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        }
    
        /**
         * Сброс накопленных данных из Redis в MySQL.
         */
        public function flushImpressionsToDb(int $bannerId): void
        {
            $this->correlationId = Str::uuid();
    
            try {
                $key = "ad_impressions_buffer";
                $count = Redis::hget($key, (string)$bannerId);
                
                if ($count && $count > 0) {
                    Log::channel('performance')->info('HighLoadTrafficOrchestrator: flushing impressions', [
                        'correlation_id' => $this->correlationId,
                        'banner_id' => $bannerId,
                        'count' => $count,
                    ]);
    
                    AdBanner::where('id', $bannerId)->increment('impressions_count', $count);
                    Redis::hdel($key, (string)$bannerId);
    
                    Log::channel('performance')->info('HighLoadTrafficOrchestrator: impressions flushed', [
                        'correlation_id' => $this->correlationId,
                        'banner_id' => $bannerId,
                        'flushed_count' => $count,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('HighLoadTrafficOrchestrator: flush failed', [
                    'correlation_id' => $this->correlationId,
                    'banner_id' => $bannerId,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        }
    
        /**
         * Оптимизация кеширования рекламной выдачи.
         * Использование тагов (Cache Tags) для моментального инвалидирования при смене ставок.
         */
        public function getCachedPlacement(string $code, callable $callback)
        {
            try {
                Log::channel('performance')->debug('HighLoadTrafficOrchestrator: getting cached placement', [
                    'correlation_id' => $this->correlationId,
                    'placement_code' => $code,
                ]);
    
                // Redis tag-based caching для мгновенного обновления аукциона
                return \Illuminate\Support\Facades\Cache::tags(['ad_placements', "placement_{$code}"])
                    ->remember("ad_data_{$code}", 300, $callback);
            } catch (Throwable $e) {
                Log::error('HighLoadTrafficOrchestrator: placement cache failed', [
                    'correlation_id' => $this->correlationId,
                    'placement_code' => $code,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
                // Fallback: вызвать callback напрямую
                return $callback();
            }
        }
    
        /**
         * Инвалидация кэша размещения при обновлении ставок.
         */
        public function invalidatePlacementCache(string $code): void
        {
            try {
                \Illuminate\Support\Facades\Cache::tags(["placement_{$code}"])->flush();
    
                Log::channel('performance')->info('HighLoadTrafficOrchestrator: placement cache invalidated', [
                    'correlation_id' => $this->correlationId,
                    'placement_code' => $code,
                ]);
            } catch (Throwable $e) {
                Log::error('HighLoadTrafficOrchestrator: cache invalidation failed', [
                    'correlation_id' => $this->correlationId,
                    'placement_code' => $code,
                    'error' => $e->getMessage(),
                ]);
            }
        }
}
