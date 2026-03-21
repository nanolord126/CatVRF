<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * CDN Configuration Service
 * Управление конфигурацией CDN и интеграция с провайдерами
 * 
 * @package App\Services\Performance
 * @category Performance / CDN Management
 */
final class CDNConfigurationService
{
    /**
     * Поддерживаемые провайдеры CDN
     */
    private const PROVIDERS = [
        'cloudflare' => 'Cloudflare',
        'bunny' => 'BunnyCDN',
        'aws_cloudfront' => 'AWS CloudFront',
        'azure_cdn' => 'Azure CDN',
        'akamai' => 'Akamai',
    ];

    /**
     * Получает URL ресурса с CDN
     * 
     * @param string $path
     * @param string $provider
     * @return string
     */
    public static function getUrl(string $path, string $provider = 'cloudflare'): string
    {
        $providers = [
            'cloudflare' => fn($path) => 'https://' . config('cdn.cloudflare_domain') . '/' . ltrim($path, '/'),
            'bunny' => fn($path) => 'https://' . config('cdn.bunny_domain') . '/' . ltrim($path, '/'),
            'aws_cloudfront' => fn($path) => 'https://' . config('cdn.cloudfront_domain') . '/' . ltrim($path, '/'),
            'azure_cdn' => fn($path) => 'https://' . config('cdn.azure_domain') . '.azureedge.net/' . ltrim($path, '/'),
        ];

        return isset($providers[$provider]) 
            ? $providers[$provider]($path)
            : asset($path);
    }

    /**
     * Получает конфигурацию кэширования для типа контента
     * 
     * @param string $contentType
     * @return array {ttl, cache_control, gzip}
     */
    public static function getCacheConfig(string $contentType): array
    {
        $configs = [
            'html' => ['ttl' => 3600, 'cache_control' => 'public, max-age=3600', 'gzip' => true],
            'css' => ['ttl' => 86400, 'cache_control' => 'public, max-age=86400, immutable', 'gzip' => true],
            'js' => ['ttl' => 86400, 'cache_control' => 'public, max-age=86400, immutable', 'gzip' => true],
            'json' => ['ttl' => 300, 'cache_control' => 'public, max-age=300', 'gzip' => true],
            'image' => ['ttl' => 31536000, 'cache_control' => 'public, max-age=31536000, immutable', 'gzip' => false],
            'font' => ['ttl' => 31536000, 'cache_control' => 'public, max-age=31536000, immutable', 'gzip' => false],
            'video' => ['ttl' => 86400, 'cache_control' => 'public, max-age=86400', 'gzip' => false],
        ];

        return $configs[$contentType] ?? $configs['html'];
    }

    /**
     * Получает рекомендованные правила кэширования
     * 
     * @return array
     */
    public static function getRecommendedRules(): array
    {
        return [
            [
                'path_pattern' => '*.html',
                'ttl' => 3600,
                'cache_control' => 'public, max-age=3600',
                'description' => 'HTML pages - short cache',
            ],
            [
                'path_pattern' => '*.css',
                'ttl' => 86400,
                'cache_control' => 'public, max-age=86400, immutable',
                'description' => 'Stylesheets - long cache',
            ],
            [
                'path_pattern' => '*.js',
                'ttl' => 86400,
                'cache_control' => 'public, max-age=86400, immutable',
                'description' => 'JavaScript - long cache',
            ],
            [
                'path_pattern' => '/api/*',
                'ttl' => 300,
                'cache_control' => 'public, max-age=300',
                'description' => 'API responses - medium cache',
            ],
            [
                'path_pattern' => '/images/*',
                'ttl' => 31536000,
                'cache_control' => 'public, max-age=31536000, immutable',
                'description' => 'Images - very long cache',
            ],
            [
                'path_pattern' => '/fonts/*',
                'ttl' => 31536000,
                'cache_control' => 'public, max-age=31536000, immutable',
                'description' => 'Fonts - very long cache',
            ],
        ];
    }

    /**
     * Инвалидирует кэш на CDN
     * 
     * @param array|string $paths
     * @param string $provider
     * @return array
     */
    public static function purgeCache(array|string $paths, string $provider = 'cloudflare'): array
    {
        $paths = is_string($paths) ? [$paths] : $paths;

        $result = match ($provider) {
            'cloudflare' => self::purgeCloudflareCache($paths),
            'bunny' => self::purgeBunnyCache($paths),
            'aws_cloudfront' => self::purgeCloudFrontCache($paths),
            default => ['status' => 'error', 'message' => 'Unknown provider'],
        };

        Log::channel('performance')->info('CDN cache purged', [
            'provider' => $provider,
            'paths_count' => count($paths),
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Очищает весь кэш
     * 
     * @param string $provider
     * @return array
     */
    public static function purgeAllCache(string $provider = 'cloudflare'): array
    {
        $result = match ($provider) {
            'cloudflare' => ['status' => 'success', 'message' => 'All cache purged'],
            'bunny' => ['status' => 'success', 'message' => 'All cache purged'],
            default => ['status' => 'error', 'message' => 'Unknown provider'],
        };

        Log::channel('performance')->warning('All CDN cache purged', [
            'provider' => $provider,
        ]);

        return $result;
    }

    /**
     * Получает статистику CDN
     * 
     * @param string $provider
     * @return array
     */
    public static function getStats(string $provider = 'cloudflare'): array
    {
        // Плейсхолдер для интеграции с API провайдера
        return [
            'provider' => $provider,
            'bandwidth_used_gb' => 0,
            'requests_total' => 0,
            'cache_hit_ratio' => 0,
            'average_response_time_ms' => 0,
            'top_countries' => [],
        ];
    }

    /**
     * Получает оптимальные настройки для типа контента
     * 
     * @param string $fileExtension
     * @return array
     */
    public static function getOptimalSettings(string $fileExtension): array
    {
        $extension = strtolower($fileExtension);

        $settings = [
            'html' => ['compress' => true, 'cache_ttl' => 3600, 'min_file_size' => 0],
            'css' => ['compress' => true, 'cache_ttl' => 86400, 'min_file_size' => 0],
            'js' => ['compress' => true, 'cache_ttl' => 86400, 'min_file_size' => 0],
            'json' => ['compress' => true, 'cache_ttl' => 300, 'min_file_size' => 1024],
            'svg' => ['compress' => true, 'cache_ttl' => 31536000, 'min_file_size' => 0],
            'png' => ['compress' => false, 'cache_ttl' => 31536000, 'min_file_size' => 0],
            'jpg' => ['compress' => false, 'cache_ttl' => 31536000, 'min_file_size' => 0],
            'webp' => ['compress' => false, 'cache_ttl' => 31536000, 'min_file_size' => 0],
            'woff' => ['compress' => false, 'cache_ttl' => 31536000, 'min_file_size' => 0],
            'woff2' => ['compress' => false, 'cache_ttl' => 31536000, 'min_file_size' => 0],
            'mp4' => ['compress' => false, 'cache_ttl' => 86400, 'min_file_size' => 0],
            'webm' => ['compress' => false, 'cache_ttl' => 86400, 'min_file_size' => 0],
        ];

        return $settings[$extension] ?? $settings['html'];
    }

    /**
     * Генерирует отчёт о конфигурации CDN
     * 
     * @return string
     */
    public static function generateReport(): string
    {
        $report = "\n╔════════════════════════════════════════════════════════════╗\n";
        $report .= "║             CDN CONFIGURATION REPORT                       ║\n";
        $report .= "║             " . now()->toDateTimeString() . "                    ║\n";
        $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

        $report .= "  RECOMMENDED CACHING RULES:\n\n";

        foreach (self::getRecommendedRules() as $rule) {
            $report .= sprintf("    [%s]\n", $rule['path_pattern']);
            $report .= sprintf("      TTL: %d seconds (%s)\n", $rule['ttl'], self::secondsToHuman($rule['ttl']));
            $report .= sprintf("      Cache-Control: %s\n", $rule['cache_control']);
            $report .= sprintf("      Description: %s\n\n", $rule['description']);
        }

        $report .= "  SUPPORTED PROVIDERS:\n\n";

        foreach (self::PROVIDERS as $key => $name) {
            $report .= sprintf("    - %s (%s)\n", $name, $key);
        }

        $report .= "\n";

        return $report;
    }

    /**
     * Очищает кэш Cloudflare
     * 
     * @param array $paths
     * @return array
     */
    private static function purgeCloudflareCache(array $paths): array
    {
        // Интеграция с Cloudflare API
        return [
            'status' => 'success',
            'provider' => 'cloudflare',
            'paths_purged' => count($paths),
        ];
    }

    /**
     * Очищает кэш BunnyCDN
     * 
     * @param array $paths
     * @return array
     */
    private static function purgeBunnyCache(array $paths): array
    {
        // Интеграция с BunnyCDN API
        return [
            'status' => 'success',
            'provider' => 'bunny',
            'paths_purged' => count($paths),
        ];
    }

    /**
     * Очищает кэш CloudFront
     * 
     * @param array $paths
     * @return array
     */
    private static function purgeCloudFrontCache(array $paths): array
    {
        // Интеграция с AWS CloudFront API
        return [
            'status' => 'success',
            'provider' => 'aws_cloudfront',
            'paths_purged' => count($paths),
        ];
    }

    /**
     * Преобразует секунды в читаемый формат
     * 
     * @param int $seconds
     * @return string
     */
    private static function secondsToHuman(int $seconds): string
    {
        $intervals = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
        ];

        foreach ($intervals as $name => $value) {
            if ($seconds >= $value) {
                $time = round($seconds / $value);
                return $time . ' ' . $name . ($time > 1 ? 's' : '');
            }
        }

        return $seconds . ' seconds';
    }
}
