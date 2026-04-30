<?php

declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Psr\Log\LoggerInterface;

use App\Domains\Travel\Models\Tour;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Log\LogManager;

/**
 * Tourism Virtual Tour Service
 *
 * Service for managing virtual 360° tours and AR viewing.
 * Integrates with external virtual tour providers (Matterport, Kuula, etc.).
 */
final readonly class TourismVirtualTourService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly Cache $cache,
        private readonly RedisConnection $redis,
        private readonly HttpFactory $http,
        private readonly FilesystemFactory $storage,) {}

    /**
     * Get virtual tour URL for a tour.
     *
     * @param  int  $tourId  Tour ID
     * @param  string  $correlationId  Correlation ID for tracing
     * @return string|null Virtual tour URL or null if not available
     */
    public function getVirtualTourUrl(int $tourId, string $correlationId = ''): ?string
    {
        $tour = Tour::find($tourId);

        if (! $tour) {
            $this->log->error('Tour not found for virtual tour', [
                'tour_id' => $tourId,
                'correlation_id' => $correlationId,
            ]);

            return null;
        }

        if ($tour->virtual_tour_url) {
            return $tour->virtual_tour_url;
        }

        if (! $tour->virtual_tour_enabled) {
            return null;
        }

        $virtualTourUrl = $this->generateVirtualTourUrl($tour, $correlationId);

        if ($virtualTourUrl) {
            $tour->update(['virtual_tour_url' => $virtualTourUrl]);
        }

        return $virtualTourUrl;
    }

    /**
     * Check if AR viewing is available for a tour.
     *
     * @param  int  $tourId  Tour ID
     * @param  string  $correlationId  Correlation ID for tracing
     * @return bool True if AR viewing is available
     */
    public function isARAvailable(int $tourId, string $correlationId = ''): bool
    {
        $tour = Tour::find($tourId);

        if (! $tour) {
            return false;
        }

        return $tour->metadata['ar_enabled'] ?? false;
    }

    /**
     * Get AR model URL for a tour.
     *
     * @param  int  $tourId  Tour ID
     * @param  string  $correlationId  Correlation ID for tracing
     * @return string|null AR model URL or null
     */
    public function getARModelUrl(int $tourId, string $correlationId = ''): ?string
    {
        $tour = Tour::find($tourId);

        if (! $tour) {
            return null;
        }

        $arModelPath = $tour->metadata['ar_model_path'] ?? null;

        if (! $arModelPath) {
            return null;
        }

        return $this->storage->disk('public')->url($arModelPath);
    }

    /**
     * Track virtual tour view for analytics.
     *
     * @param  int  $tourId  Tour ID
     * @param  int  $userId  User ID
     * @param  string  $correlationId  Correlation ID for tracing
     */
    public function trackVirtualTourView(int $tourId, int $userId, string $correlationId = ''): void
    {
        $viewKey = "virtual_tour_views:{$tourId}:{$userId}:".date('Y-m-d');

        $views = $this->redis->incr($viewKey);
        $this->redis->expire($viewKey, 86400);

        $tourTotalKey = "virtual_tour_views:total:{$tourId}";
        $this->redis->incr($tourTotalKey);
        $this->redis->expire($tourTotalKey, 2592000);

        $this->log->$this->logger->info('Virtual tour view tracked', [
            'tour_id' => $tourId,
            'user_id' => $userId,
            'views_today' => $views,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Get virtual tour view statistics.
     *
     * @param  int  $tourId  Tour ID
     * @param  string  $correlationId  Correlation ID for tracing
     * @return array Statistics data
     */
    public function getVirtualTourStats(int $tourId, string $correlationId = ''): array
    {
        $tourTotalKey = "virtual_tour_views:total:{$tourId}";
        $totalViews = (int) $this->redis->get($tourTotalKey);

        $uniqueViewersKey = "virtual_tour_viewers:unique:{$tourId}";
        $uniqueViewers = (int) $this->redis->scard($uniqueViewersKey);

        return [
            'tour_id' => $tourId,
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Generate virtual tour URL for a tour.
     *
     * @param  Tour  $tour  Tour model
     * @param  string  $correlationId  Correlation ID for tracing
     * @return string|null Generated virtual tour URL or null
     */
    private function generateVirtualTourUrl(Tour $tour, string $correlationId = ''): ?string
    {
        $provider = config('services.virtual_tour.provider', 'matterport');

        try {
            return match ($provider) {
                'matterport' => $this->getMatterportUrl($tour, $correlationId),
                'kuula' => $this->getKuulaUrl($tour, $correlationId),
                'custom' => $this->getCustomVirtualTourUrl($tour, $correlationId),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->log->error('Failed to generate virtual tour URL', [
                'tour_id' => $tour->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return null;
        }
    }

    /**
     * Get Matterport virtual tour URL.
     */
    private function getMatterportUrl(Tour $tour, string $correlationId = ''): ?string
    {
        $matterportId = $tour->metadata['matterport_id'] ?? null;

        if (! $matterportId) {
            return null;
        }

        $apiKey = config('services.virtual_tour.matterport.api_key');

        $response = $this->http->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->get("https://api.matterport.com/api/models/{$matterportId}");

        if (! $response->successful()) {
            $this->log->error('Matterport API request failed', [
                'tour_id' => $tour->id,
                'matterport_id' => $matterportId,
                'status' => $response->status(),
                'correlation_id' => $correlationId,
            ]);

            return null;
        }

        $data = $response->json();

        return $data['url'] ?? null;
    }

    /**
     * Get Kuula virtual tour URL.
     */
    private function getKuulaUrl(Tour $tour, string $correlationId = ''): ?string
    {
        $kuulaId = $tour->metadata['kuula_id'] ?? null;

        if (! $kuulaId) {
            return null;
        }

        return "https://kuula.co/share/{$kuulaId}";
    }

    /**
     * Get custom virtual tour URL.
     */
    private function getCustomVirtualTourUrl(Tour $tour, string $correlationId = ''): ?string
    {
        $customPath = $tour->metadata['virtual_tour_path'] ?? null;

        if (! $customPath) {
            return null;
        }

        return $this->storage->disk('public')->url($customPath);
    }
}
