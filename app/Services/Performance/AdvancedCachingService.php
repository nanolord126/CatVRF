<?php declare(strict_types=1);

namespace App\Services\Performance;




use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final readonly class AdvancedCachingService
{
    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}


    private const TIER_HOT_TTL = 300;        // 5 минут (горячие данные)
        private const TIER_WARM_TTL = 3600;     // 1 час (тёплые)
        private const TIER_COLD_TTL = 86400;    // 24 часа (холодные)
        private const COMPRESS_THRESHOLD = 1024; // Сжимать при > 1KB

        /**
         * Кэширует данные с автоматической компрессией и многоуровневой стратегией
         *
         * @param string $key
         * @param mixed $data
         * @param int $ttl
         * @param string $tier
         * @return bool
         */
        public function set(
            string $key,
            mixed $data,
            int $ttl = self::TIER_WARM_TTL,
            string $tier = 'warm'
        ): bool {
            try {
                $serialized = serialize($data);
                $size = strlen($serialized);

                // Компрессуем большие данные
                if ($size > self::COMPRESS_THRESHOLD) {
                    $compressed = gzcompress($serialized, 9);
                    $key = "{$key}:compressed";
                    $serialized = $compressed;
                }

                // Определяем реальный TTL по уровню
                $finalTtl = match ($tier) {
                    'cold' => self::TIER_COLD_TTL,
                    default => self::TIER_WARM_TTL,
                };

                return $this->cache->put($key, $serialized, $finalTtl);

            } catch (\Throwable $e) {
                $this->logger->channel('performance')->warning('Cache set failed', [
                    'key' => $key,
                    'tier' => $tier,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }
        }

        /**
         * Получает кэшированные данные с автоматической декомпрессией
         *
         * @param string $key
         * @return mixed
         */
        public function get(string $key): mixed
        {
            try {
                // Пытаемся получить сжатый вариант
                $compressedKey = "{$key}:compressed";

                if ($this->cache->has($compressedKey)) {
                    $compressed = $this->cache->get($compressedKey);
                    $decompressed = gzuncompress($compressed);
                    return unserialize($decompressed);
                }

                // Иначе обычный вариант
                if ($this->cache->has($key)) {
                    $data = $this->cache->get($key);
                    return unserialize($data);
                }

                throw new \RuntimeException("Cache miss for key: {$key}");

            } catch (\RuntimeException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->logger->channel('performance')->warning('Cache get failed', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                throw new \RuntimeException("Cache get failed for key: {$key}", 0, $e);
            }
        }

        /**
         * Кэширует результат функции с автоматической инвалидацией по паттернам
         *
         * @param string $key
         * @param callable $callback
         * @param int $ttl
         * @param array $tags
         * @return mixed
         */
        public function remember(
            string $key,
            callable $callback,
            int $ttl = self::TIER_WARM_TTL,
            array $tags = []
        ): mixed {
            // Пытаемся получить из кэша
            $cached = $this->get($key);
            if ($cached !== null) {
                return $cached;
            }

            // Вычисляем новое значение
            $result = $callback();

            // Кэшируем с тегами для групповой инвалидации
            $this->set($key, $result, $ttl);

            if (!empty($tags)) {
                $this->tagCache($key, $tags);
            }

            return $result;
        }

        /**
         * Инвалидирует кэш по паттерну (например, "revenue:*")
         *
         * @param string $pattern
         * @return int
         */
        public function invalidatePattern(string $pattern): int
        {
            try {
                $keys = $this->searchKeys($pattern);
                $count = 0;

                foreach ($keys as $key) {
                    if ($this->cache->forget($key)) {
                        $count++;
                    }
                    // Забываем и сжатый вариант
                    $this->cache->forget("{$key}:compressed");
                }

                $this->logger->channel('performance')->info('Cache pattern invalidated', [
                    'pattern' => $pattern,
                    'keys_cleared' => $count,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                return $count;

            } catch (\Throwable $e) {
                $this->logger->channel('performance')->error('Pattern invalidation failed', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return 0;
            }
        }

        /**
         * Проверяет размер данных в кэше (для мониторинга)
         *
         * @param string $key
         * @return int
         */
        public function getSize(string $key): int
        {
            $data = $this->cache->get($key);
            return $data ? strlen($data) : 0;
        }

        /**
         * Получает статистику кэша Redis
         *
         * @return array
         */
        public function getStats(): array
        {
            try {
                $redis = Redis::connection();
                $info = $redis->info();

                return [
                    'used_memory' => $info['used_memory'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                    'evicted_keys' => $info['evicted_keys'] ?? 0,
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                ];

            } catch (\Throwable $e) {
                $this->logger->channel('performance')->warning('Failed to get cache stats', [
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return [];
            }
        }

        /**
         * Теги для групповой инвалидации
         *
         * @param string $key
         * @param array $tags
         * @return void
         */
        private function tagCache(string $key, array $tags): void
        {
            foreach ($tags as $tag) {
                $tagKey = "tag:{$tag}";
                $existingKeys = $this->cache->get($tagKey, []);
                if (!in_array($key, $existingKeys)) {
                    $existingKeys[] = $key;
                    $this->cache->put($tagKey, $existingKeys, self::TIER_COLD_TTL);
                }
            }
        }

        /**
         * Поиск ключей по паттерну (используется Redis)
         *
         * @param string $pattern
         * @return array
         */
        private function searchKeys(string $pattern): array
        {
            try {
                $redis = Redis::connection();
                return $redis->keys($pattern);
            } catch (\Throwable $e) {
                $this->logger->channel('performance')->warning('Key search failed', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return [];
            }
        }
}
