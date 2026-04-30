<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * Base Cached Widget
 *
 * Base class for Filament widgets with caching support.
 * Reduces database load for widgets that don't need real-time data.
 */
abstract class BaseCachedWidget extends Widget
{
    public function __construct(private readonly CacheManager $cacheManager,
        protected readonly CacheManager $cache,
        protected readonly Guard $guard,) {
        parent::__construct();
    }
    /**
     * Cache TTL in seconds (default: 5 minutes)
     */
    protected static int $cacheTtl = 300;

    /**
     * Enable/disable caching (default: true)
     */
    protected static bool $enableCache = true;

    /**
     * Cache key prefix
     */
    protected static string $cacheKeyPrefix = 'filament_widget';

    /**
     * Invalidate cache for this widget
     */
    public static function invalidateCache(): void
    {
        $key = sprintf(
            '%s_%s',
            static::$cacheKeyPrefix,
            class_basename(static::class)
        );

        // Clear all variants of this widget cache
        app(CacheManager::class)->forgetMatching($key.'*');
    }

    /**
     * Clear all filament widget caches
     */
    public static function clearAllWidgetCaches(): void
    {
        app(CacheManager::class)->forgetMatching('filament_widget_*');
    }

    /**
     * Get cache key for this widget
     */
    protected function getCacheKey(): string
    {
        return sprintf(
            '%s_%s_%s',
            static::$cacheKeyPrefix,
            class_basename(static::class),
            $this->guard->id() ?? 'guest'
        );
    }

    /**
     * Get cached data
     */
    protected function getCachedData(callable $callback): mixed
    {
        if (! static::$enableCache) {
            return $callback();
        }

        return $this->cache->remember(
            $this->getCacheKey(),
            static::$cacheTtl,
            $callback
        );
    }
}
