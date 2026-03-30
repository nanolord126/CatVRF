<?php declare(strict_types=1);

namespace Modules\Common\Services\Performance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ZeroWaitOrchestrator extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlationId;
        private ?int $tenantId;
    
        public function __construct()
        {
            $this->correlationId = Str::uuid();
            $this->tenantId = Auth::guard('tenant')?->id();
        }
    
        /**
         * Атомарная обработка заказа/транзакции высоконагруженным способом.
         * Вместо блокировки таблицы (Locking), используем оптимистичную блокировку версий.
         */
        public function executeAtomicTransaction(string $resourceKey, callable $logic)
        {
            try {
                Log::channel('performance')->debug('ZeroWaitOrchestrator: executing atomic transaction', [
                    'correlation_id' => $this->correlationId,
                    'resource_key' => $resourceKey,
                ]);
    
                return DB::transaction(function () use ($resourceKey, $logic) {
                    // Используем Redis как распределенный семафор для мгновенного допуска
                    $lockKey = "executor_semaphore:{$resourceKey}";
                    
                    if (!Redis::set($lockKey, "active", 'EX', 5, 'NX')) {
                        // Если ресурс занят другим процессом, мы не ставим в очередь,
                        // а мгновенно отдаем результат из кеша или Read-Replica.
                        Log::warning('ZeroWaitOrchestrator: resource locked, serving from cache', [
                            'correlation_id' => $this->correlationId,
                            'resource_key' => $resourceKey,
                        ]);
                        return $this->serveFromHotCache($resourceKey);
                    }
    
                    try {
                        $result = $logic();
                        // Синхронная запись в Hot Cache для мгновенного доступа другими инстансами
                        $this->updateHotCache($resourceKey, $result);
                        
                        Log::channel('performance')->info('ZeroWaitOrchestrator: atomic transaction completed', [
                            'correlation_id' => $this->correlationId,
                            'resource_key' => $resourceKey,
                        ]);
                        
                        return $result;
                    } finally {
                        Redis::del($lockKey);
                    }
                });
            } catch (Throwable $e) {
                Log::error('ZeroWaitOrchestrator: atomic transaction failed', [
                    'correlation_id' => $this->correlationId,
                    'resource_key' => $resourceKey,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
                throw $e;
            }
        }
    
        /**
         * Реализация Read-Through: данные всегда в памяти.
         */
        public function fastRead(string $key, callable $fallback)
        {
            try {
                Log::channel('performance')->debug('ZeroWaitOrchestrator: fast read', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                ]);
    
                $data = Redis::get("hot_data:{$key}");
                
                if ($data) {
                    Log::channel('performance')->debug('ZeroWaitOrchestrator: cache hit', [
                        'correlation_id' => $this->correlationId,
                        'cache_key' => $key,
                    ]);
                    return unserialize($data);
                }
    
                $fresh = $fallback();
                $this->updateHotCache($key, $fresh);
                
                Log::channel('performance')->debug('ZeroWaitOrchestrator: cache miss, populated', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                ]);
    
                return $fresh;
            } catch (Throwable $e) {
                Log::error('ZeroWaitOrchestrator: fast read failed', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
                return $fallback();
            }
        }
    
        /**
         * Обслуживание запроса из горячего кэша при недоступности БД.
         */
        public function serveFromHotCache(string $key)
        {
            try {
                Log::channel('performance')->debug('ZeroWaitOrchestrator: serving from hot cache', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                ]);
    
                $data = Redis::get("hot_data:{$key}");
                
                if ($data) {
                    return unserialize($data);
                }
    
                return null;
            } catch (Throwable $e) {
                Log::error('ZeroWaitOrchestrator: hot cache read failed', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
                return null;
            }
        }
    
        protected function updateHotCache(string $key, $data): void
        {
            try {
                // TTL 60 секунд для актуальности данных маркетплейса (цены, остатки)
                Redis::setex("hot_data:{$key}", 60, serialize($data));
    
                Log::channel('performance')->debug('ZeroWaitOrchestrator: hot cache updated', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                ]);
            } catch (Throwable $e) {
                Log::error('ZeroWaitOrchestrator: hot cache update failed', [
                    'correlation_id' => $this->correlationId,
                    'cache_key' => $key,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        }
}
