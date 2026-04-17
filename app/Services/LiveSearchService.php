<?php declare(strict_types=1);

namespace App\Services;

use App\Domains\Search\Models\SearchIndex;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class LiveSearchService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
    ) {}

    private const SEARCH_CACHE_TTL = 300; // 5 minutes
    private const MAX_RESULTS = 50;

        /**
         * Выполняет поиск по документам
         */
        public function searchDocuments(
            int $tenantId,
            string $query,
            array $filters = [],
            string $correlationId = null
        ): Collection {
            $correlationId ??= Str::uuid()->toString();

            try {
                $cacheKey = "search:docs:{$tenantId}:" . md5($query . json_encode($filters));
                $cached = $this->cache->get($cacheKey);

                if ($cached !== null) {
                    return collect($cached);
                }

                // Build search query
                $searchQuery = SearchIndex::where('tenant_id', $tenantId)
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                          ->orWhere('content', 'like', "%{$query}%");
                    });

                // Apply filters
                if (!empty($filters['type'])) {
                    $searchQuery->where('searchable_type', $filters['type']);
                }

                if (!empty($filters['status'])) {
                    $searchQuery->whereJsonContains('metadata->status', $filters['status']);
                }

                $results = $searchQuery
                    ->orderBy('ranking_score', 'desc')
                    ->limit(self::MAX_RESULTS)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->uuid,
                            'type' => $item->searchable_type,
                            'title' => $item->title,
                            'content' => $item->content,
                            'metadata' => $item->metadata,
                            'ranking_score' => $item->ranking_score,
                        ];
                    });

                $this->cache->put($cacheKey, $results->toArray(), self::SEARCH_CACHE_TTL);

                $this->logger->channel('audit')->debug('Document search performed', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'query' => $query,
                    'results_count' => $results->count(),
                ]);

                return $results;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Document search failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Выполняет поиск по пользователям
         */
        public function searchUsers(
            int $tenantId,
            string $query,
            string $correlationId = null
        ): Collection {
            $correlationId ??= Str::uuid()->toString();

            try {
                $cacheKey = "search:users:{$tenantId}:" . md5($query);
                $cached = $this->cache->get($cacheKey);

                if ($cached !== null) {
                    return collect($cached);
                }

                $results = collect();

                if (!empty($query)) {
                    // DB поиск пользователей по имени/email
                    $users = $this->db->table('users')
                        ->where('tenant_id', $tenantId)
                        ->where(function ($q) use ($query) {
                            $q->where('name', 'like', "%{$query}%")
                              ->orWhere('email', 'like', "%{$query}%");
                        })
                        ->limit(self::MAX_RESULTS)
                        ->get();

                    $results = collect($users)->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role ?? null,
                        ];
                    });
                }

                $this->cache->put($cacheKey, $results->toArray(), self::SEARCH_CACHE_TTL);

                return $results;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('User search failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Применяет фильтры к результатам
         */
        public function applyFilters(
            Collection $results,
            array $filters
        ): Collection {
            if (empty($filters)) {
                return $results;
            }

            return $results->filter(function ($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (isset($item[$key]) && $item[$key] !== $value) {
                        return false;
                    }
                }

                return true;
            });
        }

        /**
         * Получает популярные поисковые запросы
         */
        public function getPopularSearches(int $tenantId): array
        {
            $cacheKey = "search:popular:{$tenantId}";

            return $this->cache->get($cacheKey, []);
        }

        /**
         * Сохраняет поисковый запрос в историю
         */
        public function recordSearch(
            int $userId,
            int $tenantId,
            string $query,
            int $resultsCount
        ): void {
            if (strlen($query) < 2) {
                return;
            }

            $cacheKey = "search:history:{$tenantId}:{$userId}";
            $history = $this->cache->get($cacheKey, []);

            $history[] = [
                'query' => $query,
                'results_count' => $resultsCount,
                'timestamp' => now()->toIso8601String(),
            ];

            // Сохраняем только последние 50 запросов
            if (count($history) > 50) {
                $history = array_slice($history, -50);
            }

            $this->cache->put($cacheKey, $history, 2592000); // 30 дней
        }

        /**
         * Получает историю поисков пользователя
         */
        public function getSearchHistory(int $userId, int $tenantId): array
        {
            $cacheKey = "search:history:{$tenantId}:{$userId}";

            return $this->cache->get($cacheKey, []);
        }

        /**
         * Очищает историю поисков
         */
        public function clearSearchHistory(int $userId, int $tenantId): bool
        {
            $cacheKey = "search:history:{$tenantId}:{$userId}";
            $this->cache->forget($cacheKey);

            return true;
        }
}
