<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

/**
 * Сервис кэширования маркетплейса с tags-based инвалидацией
 */
final class MarketplaceCacheService
{
    private const PREFIX = 'marketplace:';
    private const DEFAULT_TTL = 300; // 5 минут

    public function __construct(
        private readonly Repository $cache,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly int $ttl,
    ) {}

    /**
     * Получить позицию из кэша
     */
    public function getListing(string $uuid): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $key = $this->listingKey($uuid);
        $data = $this->cache->get($key);

        if ($data !== null) {
            $this->logger->debug('Cache hit for listing', ['uuid' => $uuid]);
        }

        return $data;
    }

    /**
     * Сохранить позицию в кэш
     */
    public function setListing(string $uuid, array $data): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->listingKey($uuid);
        $tags = $this->listingTags($uuid);

        $this->cache->put($key, $data, $this->ttl);
        $this->cache->tags($tags)->put($key, $data, $this->ttl);

        $this->logger->debug('Cached listing', ['uuid' => $uuid]);
    }

    /**
     * Удалить позицию из кэша
     */
    public function forgetListing(string $uuid): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->listingKey($uuid);
        $tags = $this->listingTags($uuid);

        $this->cache->forget($key);
        $this->cache->tags($tags)->forget($key);

        $this->logger->debug('Forgot listing from cache', ['uuid' => $uuid]);
    }

    /**
     * Получить поиск из кэша
     */
    public function getSearch(array $filters): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $key = $this->searchKey($filters);
        $data = $this->cache->get($key);

        if ($data !== null) {
            $this->logger->debug('Cache hit for search', ['filters' => $filters]);
        }

        return $data;
    }

    /**
     * Сохранить поиск в кэш
     */
    public function setSearch(array $filters, array $data): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->searchKey($filters);
        $tags = $this->searchTags($filters);

        $this->cache->tags($tags)->put($key, $data, $this->ttl);

        $this->logger->debug('Cached search', ['filters' => $filters]);
    }

    /**
     * Получить категории из кэша
     */
    public function getCategories(): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $key = $this->categoriesKey();
        return $this->cache->get($key);
    }

    /**
     * Сохранить категории в кэш
     */
    public function setCategories(array $data): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->categoriesKey();
        $tags = ['marketplace:categories'];

        $this->cache->tags($tags)->put($key, $data, $this->ttl * 2); // Категории кэшируем дольше

        $this->logger->debug('Cached categories');
    }

    /**
     * Получить рекомендации из кэша
     */
    public function getRecommendations(int $userId, ?array $categories = null): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $key = $this->recommendationsKey($userId, $categories);
        return $this->cache->get($key);
    }

    /**
     * Сохранить рекомендации в кэш
     */
    public function setRecommendations(int $userId, array $data, ?array $categories = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->recommendationsKey($userId, $categories);
        $tags = array_merge(['marketplace:recommendations'], $categories !== null ? array_map(fn($c) => "marketplace:category:{$c}", $categories) : []);

        $this->cache->tags($tags)->put($key, $data, $this->ttl);

        $this->logger->debug('Cached recommendations', ['user_id' => $userId]);
    }

    /**
     * Инвалидировать кэш для позиции
     */
    public function invalidateListing(string $uuid): void
    {
        if (!$this->enabled) {
            return;
        }

        $tags = $this->listingTags($uuid);
        $this->cache->tags($tags)->flush();

        $this->logger->debug('Invalidated listing cache', ['uuid' => $uuid]);
    }

    /**
     * Инвалидировать кэш для вертикали
     */
    public function invalidateVertical(VerticalSource $source): void
    {
        if (!$this->enabled) {
            return;
        }

        $tags = ["marketplace:vertical:{$source->value}"];
        $this->cache->tags($tags)->flush();

        $this->logger->debug('Invalidated vertical cache', ['vertical' => $source->value]);
    }

    /**
     * Инвалидировать кэш для категории
     */
    public function invalidateCategory(string $category): void
    {
        if (!$this->enabled) {
            return;
        }

        $tags = ["marketplace:category:{$category}"];
        $this->cache->tags($tags)->flush();

        $this->logger->debug('Invalidated category cache', ['category' => $category]);
    }

    /**
     * Инвалидировать весь кэш маркетплейса
     */
    public function invalidateAll(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->tags(['marketplace'])->flush();

        $this->logger->info('Invalidated all marketplace cache');
    }

    /**
     * Получить статистику кэша
     */
    public function getStats(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'ttl' => $this->ttl,
            'driver' => config('cache.default'),
        ];
    }

    private function listingKey(string $uuid): string
    {
        return self::PREFIX . "listing:{$uuid}";
    }

    private function listingTags(string $uuid): array
    {
        return ['marketplace', 'marketplace:listings', "marketplace:listing:{$uuid}"];
    }

    private function searchKey(array $filters): string
    {
        $hash = md5(json_encode($filters));
        return self::PREFIX . "search:{$hash}";
    }

    private function searchTags(array $filters): array
    {
        $tags = ['marketplace', 'marketplace:search'];

        if (isset($filters['vertical'])) {
            $tags[] = "marketplace:vertical:{$filters['vertical']}";
        }

        if (isset($filters['category'])) {
            $tags[] = "marketplace:category:{$filters['category']}";
        }

        return $tags;
    }

    private function categoriesKey(): string
    {
        return self::PREFIX . 'categories';
    }

    private function recommendationsKey(int $userId, ?array $categories): string
    {
        $categorySuffix = $categories !== null ? ':' . implode(',', $categories) : '';
        return self::PREFIX . "recommendations:user:{$userId}{$categorySuffix}";
    }

    public static function fromConfig(): self
    {
        return new self(
            cache()->store(),
            logger(),
            config('marketplace.cache.enabled', true),
            config('marketplace.cache.ttl', self::DEFAULT_TTL),
        );
    }
}
