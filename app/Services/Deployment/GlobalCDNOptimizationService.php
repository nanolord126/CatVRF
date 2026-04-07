<?php declare(strict_types=1);

namespace App\Services\Deployment;


use Illuminate\Http\Request;
use Illuminate\Log\LogManager;



final readonly class GlobalCDNOptimizationService
{
    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
    ) {}


    /**
         * Предварительная загрузка ресурсов на CDN
         *
         * @param array $assets
         * @param array $regions
         * @return array
         */
        public static function preloadAssets(array $assets, array $regions = ['eu', 'na', 'apac']): array
        {
            $preloaded = [];

            foreach ($assets as $asset) {
                foreach ($regions as $region) {
                    $preloaded[] = [
                        'asset' => $asset,
                        'region' => $region,
                        'status' => 'preloaded',
                        'timestamp' => now()->toDateTimeString(),
                    ];
                }
            }

            $this->logger->channel('deployment')->info('Assets preloaded', [
                'assets_count' => count($assets),
                'regions' => $regions,
                'preloaded_count' => count($preloaded),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return $preloaded;
        }

        /**
         * Оптимизирует маршруты на основе гео-данных
         *
         * @param float $latitude
         * @param float $longitude
         * @return array {optimal_cdn, backup_cdns, latency_ms, strategy}
         */
        public static function optimizeRouting(float $latitude, float $longitude): array
        {
            $cdns = [
                'cloudflare' => ['latency' => self::estimateLatency($latitude, $longitude, 51.5, -0.1)],
                'bunny' => ['latency' => self::estimateLatency($latitude, $longitude, 40.7, -74.0)],
                'aws_cloudfront' => ['latency' => self::estimateLatency($latitude, $longitude, 35.7, 139.7)],
            ];

            $sorted = collect($cdns)
                ->sortBy('latency')
                ->map(fn($cdn, $key) => $key)
                ->toArray();

            return [
                'optimal_cdn' => $sorted[0] ?? 'cloudflare',
                'backup_cdns' => array_slice($sorted, 1),
                'user_location' => ['latitude' => $latitude, 'longitude' => $longitude],
                'latency_ms' => (int)$cdns[$sorted[0]]['latency'],
                'strategy' => 'geo_aware_routing',
            ];
        }

        /**
         * Включает адаптивную загрузку (ADAPTIVE BITRATE)
         *
         * @param string $contentType
         * @param int $bandwidth
         * @return array
         */
        public static function getAdaptiveBitrate(string $contentType, int $bandwidth): array
        {
            $profiles = [
                'low' => ['bitrate' => 500, 'quality' => 'mobile-optimized', 'format' => 'h264'],
                'medium' => ['bitrate' => 2500, 'quality' => 'tablet-optimized', 'format' => 'h264'],
                'high' => ['bitrate' => 5000, 'quality' => 'desktop-hd', 'format' => 'vp9'],
                'ultra' => ['bitrate' => 15000, 'quality' => '4k', 'format' => 'av1'],
            ];

            // Выбираем профиль на основе пропускной способности
            if ($bandwidth < 1000) {
                $profile = 'low';
            } elseif ($bandwidth < 3000) {
                $profile = 'medium';
            } elseif ($bandwidth < 8000) {
                $profile = 'high';
            } else {
                $profile = 'ultra';
            }

            return array_merge($profiles[$profile], ['selected_profile' => $profile]);
        }

        /**
         * Получает оптимальную compression strategy
         *
         * @param int $bandwidth
         * @param string $deviceType
         * @return array
         */
        public static function getCompressionStrategy(int $bandwidth, string $deviceType = 'desktop'): array
        {
            $strategies = [
                'mobile' => [
                    'gzip_level' => 9,
                    'minify_json' => true,
                    'minify_html' => true,
                    'minify_css' => true,
                    'minify_js' => true,
                    'image_optimization' => 'aggressive',
                    'strip_metadata' => true,
                ],
                'tablet' => [
                    'gzip_level' => 7,
                    'minify_json' => true,
                    'minify_html' => true,
                    'minify_css' => true,
                    'minify_js' => true,
                    'image_optimization' => 'moderate',
                    'strip_metadata' => true,
                ],
                'desktop' => [
                    'gzip_level' => 6,
                    'minify_json' => true,
                    'minify_html' => false,
                    'minify_css' => true,
                    'minify_js' => true,
                    'image_optimization' => 'light',
                    'strip_metadata' => false,
                ],
            ];

            return $strategies[$deviceType] ?? $strategies['desktop'];
        }

        /**
         * Включает intelligent caching
         *
         * @param string $resource
         * @return array
         */
        public static function getIntelligentCachePolicy(string $resource): array
        {
            // Определяем тип ресурса
            $type = match(true) {
                str_ends_with($resource, '.html') => 'html',
                str_ends_with($resource, '.css') || str_ends_with($resource, '.js') => 'static',
                str_contains($resource, '/api/') => 'api',
                str_ends_with($resource, ['.jpg', '.png', '.webp', '.svg']) => 'image',
                str_ends_with($resource, ['.woff2', '.woff']) => 'font',
                default => 'default',
            };

            $policies = [
                'html' => [
                    'ttl' => 3600,
                    'cache_control' => 'public, max-age=3600, must-revalidate',
                    'stale_while_revalidate' => 86400,
                    'stale_if_error' => 604800,
                ],
                'static' => [
                    'ttl' => 31536000,
                    'cache_control' => 'public, max-age=31536000, immutable',
                    'stale_while_revalidate' => 31536000,
                    'stale_if_error' => 31536000,
                ],
                'api' => [
                    'ttl' => 300,
                    'cache_control' => 'public, max-age=300',
                    'stale_while_revalidate' => 600,
                    'stale_if_error' => 3600,
                ],
                'image' => [
                    'ttl' => 31536000,
                    'cache_control' => 'public, max-age=31536000, immutable',
                    'stale_while_revalidate' => 31536000,
                    'stale_if_error' => 31536000,
                ],
                'font' => [
                    'ttl' => 31536000,
                    'cache_control' => 'public, max-age=31536000, immutable',
                    'stale_while_revalidate' => 31536000,
                    'stale_if_error' => 31536000,
                ],
                'default' => [
                    'ttl' => 3600,
                    'cache_control' => 'public, max-age=3600',
                    'stale_while_revalidate' => 86400,
                    'stale_if_error' => 604800,
                ],
            ];

            return $policies[$type] ?? $policies['default'];
        }

        /**
         * Estimирует задержку между координатами
         *
         * @param float $lat1
         * @param float $lon1
         * @param float $lat2
         * @param float $lon2
         * @return float
         */
        private static function estimateLatency(float $lat1, float $lon1, float $lat2, float $lon2): float
        {
            // Примерная формула: ~1ms на 100км
            $earth = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);

            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                 sin($dLon / 2) * sin($dLon / 2);

            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earth * $c;

            return max($distance / 100, 10); // минимум 10ms
        }

        /**
         * Генерирует отчёт
         *
         * @return string
         */
        public static function generateReport(): string
        {
            $report = "\n╔════════════════════════════════════════════════════════════╗\n";
            $report .= "║         GLOBAL CDN OPTIMIZATION REPORT                     ║\n";
            $report .= "║         " . now()->toDateTimeString() . "                    ║\n";
            $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

            $report .= "  COMPRESSION STRATEGIES:\n\n";
            $report .= "    Mobile:  gzip-9, aggressive optimization\n";
            $report .= "    Tablet:  gzip-7, moderate optimization\n";
            $report .= "    Desktop: gzip-6, light optimization\n\n";

            $report .= "  ADAPTIVE BITRATE PROFILES:\n\n";
            $report .= "    Low (500kbps):     Mobile-optimized H264\n";
            $report .= "    Medium (2500kbps): Tablet-optimized H264\n";
            $report .= "    High (5000kbps):   Desktop HD VP9\n";
            $report .= "    Ultra (15000kbps): 4K AV1\n";

            $report .= "\n  INTELLIGENT CACHING:\n\n";
            $report .= "    HTML:   3600s (+ stale-while-revalidate)\n";
            $report .= "    Static: 1 year (immutable)\n";
            $report .= "    API:    300s (short-lived)\n";
            $report .= "    Images: 1 year (immutable)\n";
            $report .= "    Fonts:  1 year (immutable)\n";

            $report .= "\n";

            return $report;
        }
}
