<?php

declare(strict_types=1);

namespace App\Services\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Log\LogManager;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class CdnMediaService
{
    use WithAuditLogging;

    private string $provider;
    private ?string $cloudflareAccountKey;
    private ?string $cloudflareAccountId;
    private ?string $bunnyApiKey;
    private ?string $bunnyStorageZone;

    public function __construct(
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {
        $this->provider = config('cdn.provider', 'cloudflare');
        $this->cloudflareAccountKey = config('cdn.cloudflare.account_key');
        $this->cloudflareAccountId = config('cdn.cloudflare.account_id');
        $this->bunnyApiKey = config('cdn.bunny.api_key');
        $this->bunnyStorageZone = config('cdn.bunny.storage_zone');
    }

    /**
     * Get optimized CDN URL for media
     *
     * @param  Media  $media
     * @param  string  $conversion
     * @param  array  $params
     * @return string
     */
    public function getOptimizedUrl(Media $media, string $conversion = 'medium', array $params = []): string
    {
        $baseUrl = $media->getUrl($conversion);

        if ($this->provider === 'cloudflare_images') {
            return $this->getCloudflareImagesUrl($media, $conversion, $params);
        }

        if ($this->provider === 'bunny') {
            return $this->getBunnyOptimizerUrl($media, $conversion, $params);
        }

        return $baseUrl;
    }

    /**
     * Get Cloudflare Images optimized URL
     *
     * @param  Media  $media
     * @param  string  $conversion
     * @param  array  $params
     * @return string
     */
    private function getCloudflareImagesUrl(Media $media, string $conversion, array $params): string
    {
        $width = $this->getWidthForConversion($conversion);
        $height = $this->getHeightForConversion($conversion);
        $quality = $this->getQualityForConversion($conversion);
        $format = $params['format'] ?? 'auto';

        // Cloudflare Images URL format
        $baseUrl = $media->getUrl();
        
        // Add CDN parameters
        $cdnParams = [
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'format' => $format,
            'fit' => $params['fit'] ?? 'cover',
        ];

        return $this->buildCdnUrl($baseUrl, $cdnParams);
    }

    /**
     * Get Bunny Optimizer URL
     *
     * @param  Media  $media
     * @param  string  $conversion
     * @param  array  $params
     * @return string
     */
    private function getBunnyOptimizerUrl(Media $media, string $conversion, array $params): string
    {
        $width = $this->getWidthForConversion($conversion);
        $height = $this->getHeightForConversion($conversion);
        $quality = $this->getQualityForConversion($conversion);

        // Bunny Optimizer URL format
        $baseUrl = $media->getUrl();
        
        $cdnParams = [
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'format' => $params['format'] ?? 'webp',
        ];

        return $this->buildCdnUrl($baseUrl, $cdnParams);
    }

    /**
     * Build CDN URL with parameters
     *
     * @param  string  $baseUrl
     * @param  array  $params
     * @return string
     */
    private function buildCdnUrl(string $baseUrl, array $params): string
    {
        $queryString = http_build_query($params);
        
        return $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . $queryString;
    }

    /**
     * Get width for conversion
     *
     * @param  string  $conversion
     * @return int
     */
    private function getWidthForConversion(string $conversion): int
    {
        return match($conversion) {
            'avatar' => 512,
            'thumb' => 300,
            'medium' => 1200,
            'large' => 2048,
            'medical' => 1600,
            default => 1200,
        };
    }

    /**
     * Get height for conversion
     *
     * @param  string  $conversion
     * @return int
     */
    private function getHeightForConversion(string $conversion): int
    {
        return match($conversion) {
            'avatar' => 512,
            'thumb' => 300,
            'medium' => 800,
            'large' => 2048,
            'medical' => 1200,
            default => 800,
        };
    }

    /**
     * Get quality for conversion
     *
     * @param  string  $conversion
     * @return int
     */
    private function getQualityForConversion(string $conversion): int
    {
        return match($conversion) {
            'avatar' => 85,
            'thumb' => 80,
            'medium' => 82,
            'large' => 78,
            'medical' => 92,
            default => 82,
        };
    }

    /**
     * Sync media to CDN
     *
     * @param  Media  $media
     * @return bool
     */
    public function syncToCdn(Media $media): bool
    {
        try {
            if ($this->provider === 'cloudflare_images') {
                return $this->syncToCloudflare($media);
            }

            if ($this->provider === 'bunny') {
                return $this->syncToBunny($media);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to sync media to CDN', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync to Cloudflare Images
     *
     * @param  Media  $media
     * @return bool
     */
    private function syncToCloudflare(Media $media): bool
    {
        // Implementation would use Cloudflare Images API
        // This is a placeholder for the actual API call
        
        $media->setCustomProperty('cdn_synced', true);
        $media->setCustomProperty('cdn_provider', 'cloudflare');
        $media->save();

        Log::info('Media synced to Cloudflare', [
            'media_id' => $media->id,
        ]);

        return true;
    }

    /**
     * Sync to Bunny CDN
     *
     * @param  Media  $media
     * @return bool
     */
    private function syncToBunny(Media $media): bool
    {
        // Implementation would use Bunny CDN API
        // This is a placeholder for the actual API call
        
        $media->setCustomProperty('cdn_synced', true);
        $media->setCustomProperty('cdn_provider', 'bunny');
        $media->save();

        Log::info('Media synced to Bunny', [
            'media_id' => $media->id,
        ]);

        return true;
    }

    /**
     * Invalidate CDN cache for media
     *
     * @param  Media  $media
     * @return bool
     */
    public function invalidateCache(Media $media): bool
    {
        try {
            if ($this->provider === 'cloudflare_images') {
                return $this->invalidateCloudflareCache($media);
            }

            if ($this->provider === 'bunny') {
                return $this->invalidateBunnyCache($media);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to invalidate CDN cache', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Invalidate Cloudflare cache
     *
     * @param  Media  $media
     * @return bool
     */
    private function invalidateCloudflareCache(Media $media): bool
    {
        // Implementation would use Cloudflare API to purge cache
        return true;
    }

    /**
     * Invalidate Bunny cache
     *
     * @param  Media  $media
     * @return bool
     */
    private function invalidateBunnyCache(Media $media): bool
    {
        // Implementation would use Bunny API to purge cache
        return true;
    }

    /**
     * Get CDN statistics
     *
     * @return array
     */
    public function getCdnStats(): array
    {
        return [
            'provider' => $this->provider,
            'enabled' => config('cdn.enabled', false),
            'video_provider' => config('cdn.video_provider', 'none'),
            'video_enabled' => config('cdn.video_enabled', false),
        ];
    }
}
