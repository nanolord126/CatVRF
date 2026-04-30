<?php

declare(strict_types=1);

namespace App\Traits;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\Conversions\Conversion;

trait HasOptimizedMedia
{
    use InteractsWithMedia;

    /**
     * Register standard media conversions for all models
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // Avatar conversion (circular, small)
        $this->addMediaConversion('avatar')
            ->width(512)
            ->height(512)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('avatar')
            ->nonQueued();

        // Thumbnail conversion (small preview)
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('images', 'portfolio', 'before_after')
            ->nonQueued();

        // Medium conversion (card view)
        $this->addMediaConversion('medium')
            ->width(1200)
            ->height(800)
            ->format('webp')
            ->quality(82)
            ->withResponsiveImages()
            ->performOnCollections('images', 'portfolio', 'before_after')
            ->queued();

        // Large conversion (detail view)
        $this->addMediaConversion('large')
            ->width(2048)
            ->format('avif')
            ->quality(78)
            ->withResponsiveImages()
            ->performOnCollections('images', 'portfolio', 'before_after')
            ->queued();

        // Medical conversion (high quality for medical records)
        $this->addMediaConversion('medical')
            ->width(1600)
            ->format('webp')
            ->quality(92)
            ->sharpen(2)
            ->performOnCollections('medical', 'documents', 'before_after')
            ->queued();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->registerMediaConversions($media);
            });

        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->registerMediaConversions($media);
            });

        $this->addMediaCollection('portfolio')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->registerMediaConversions($media);
            });

        $this->addMediaCollection('before_after')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->registerMediaConversions($media);
            });

        $this->addMediaCollection('medical')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'])
            ->registerMediaConversions(function (Media $media) {
                $this->registerMediaConversions($media);
            });

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
            ->registerMediaConversions(function (Media $media) {
                $this->registerMediaConversions($media);
            });
    }

    /**
     * Get optimized URL for media
     */
    public function getOptimizedMediaUrl(string $collection = 'images', string $conversion = 'medium'): ?string
    {
        $media = $this->getFirstMedia($collection);
        
        if (!$media) {
            return null;
        }

        if (config('cdn.enabled', false)) {
            $cdnService = app(\App\Services\Media\CdnMediaService::class);
            return $cdnService->getOptimizedUrl($media, $conversion);
        }

        return $media->getUrl($conversion);
    }

    /**
     * Get responsive srcset for images
     */
    public function getResponsiveSrcset(string $collection = 'images', string $conversion = 'medium'): ?string
    {
        $media = $this->getFirstMedia($collection);
        
        if (!$media) {
            return null;
        }

        $sizes = [300, 600, 900, 1200, 1600];
        $srcset = [];

        foreach ($sizes as $size) {
            $url = $this->getCdnUrl($media, $conversion, ['width' => $size]);
            if ($url) {
                $srcset[] = "{$url} {$size}w";
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Get CDN URL with fallback
     */
    private function getCdnUrl(Media $media, string $conversion, array $params = []): ?string
    {
        if (config('cdn.enabled', false)) {
            $cdnService = app(\App\Services\Media\CdnMediaService::class);
            return $cdnService->getOptimizedUrl($media, $conversion, $params);
        }

        return $media->getUrl($conversion);
    }
}
