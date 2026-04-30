<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Media\CdnMediaService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait UsesCdnImages
{
    /**
     * Get optimized CDN URL for media
     *
     * @param  Media|null  $media
     * @param  string  $conversion
     * @param  array  $params
     * @return string|null
     */
    public function getCdnUrl(?Media $media, string $conversion = 'medium', array $params = []): ?string
    {
        if (!$media) {
            return null;
        }

        if (!config('cdn.enabled', false)) {
            return $media->getUrl($conversion);
        }

        $cdnService = app(CdnMediaService::class);

        return $cdnService->getOptimizedUrl($media, $conversion, $params);
    }

    /**
     * Get responsive image srcset
     *
     * @param  Media|null  $media
     * @param  string  $conversion
     * @return string|null
     */
    public function getResponsiveSrcset(?Media $media, string $conversion = 'medium'): ?string
    {
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
     * Get LQIP (Low Quality Image Placeholder) URL
     *
     * @param  Media|null  $media
     * @return string|null
     */
    public function getLqipUrl(?Media $media): ?string
    {
        if (!$media) {
            return null;
        }

        return $this->getCdnUrl($media, 'thumb', ['quality' => 30, 'blur' => 5]);
    }
}
