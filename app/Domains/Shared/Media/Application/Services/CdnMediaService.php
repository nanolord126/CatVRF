<?php

declare(strict_types=1);

namespace Modules\Media\Application\Services;

use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;

/**
 * CdnMediaService — Сервис для CDN медиа файлов
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class CdnMediaService
{
    private const CONVERSION_CONFIGS = [
        'avatar' => ['width' => 512, 'height' => 512, 'quality' => 85, 'format' => 'webp'],
        'thumb' => ['width' => 300, 'height' => 300, 'quality' => 80, 'format' => 'webp'],
        'medium' => ['width' => 1200, 'height' => 800, 'quality' => 82, 'format' => 'webp'],
        'large' => ['width' => 2048, 'height' => 2048, 'quality' => 78, 'format' => 'avif'],
        'medical' => ['width' => 1600, 'height' => 1200, 'quality' => 92, 'format' => 'webp'],
    ];

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private readonly FilesystemManager $storage,
        private readonly LogManager $log,
    ) {}

    public function getOptimizedUrl(MediaFile $mediaFile, string $conversion = 'medium', array $params = []): string
    {
        if (!config('cdn.enabled')) {
            return $this->getFallbackUrl($mediaFile, $conversion);
        }

        $cdnProvider = config('cdn.provider');

        return match ($cdnProvider) {
            'cloudflare' => $this->getCloudflareImagesUrl($mediaFile, $conversion, $params),
            'bunny' => $this->getBunnyOptimizerUrl($mediaFile, $conversion, $params),
            default => $this->getFallbackUrl($mediaFile, $conversion),
        };
    }

    private function getCloudflareImagesUrl(MediaFile $mediaFile, string $conversion, array $params): string
    {
        $config = self::CONVERSION_CONFIGS[$conversion] ?? self::CONVERSION_CONFIGS['medium'];
        $imageId = $mediaFile->metadata['cdn_image_id'] ?? null;

        if ($imageId === null) {
            return $mediaFile->cdnUrl ?? $this->getFallbackUrl($mediaFile, $conversion);
        }

        $accountId = config('cdn.cloudflare.account_id');
        $variant = $params['variant'] ?? $this->getCloudflareVariant($conversion);

        return "https://{$accountId}.imagedelivery.net/{$imageId}/{$variant}";
    }

    private function getCloudflareVariant(string $conversion): string
    {
        return match ($conversion) {
            'avatar' => 'avatar',
            'thumb' => 'thumbnail',
            'medium' => 'medium',
            'large' => 'large',
            'medical' => 'medical',
            default => 'public',
        };
    }

    private function getBunnyOptimizerUrl(MediaFile $mediaFile, string $conversion, array $params): string
    {
        $config = self::CONVERSION_CONFIGS[$conversion] ?? self::CONVERSION_CONFIGS['medium'];
        $baseUrl = $mediaFile->cdnUrl ?? $this->getFallbackUrl($mediaFile, $conversion);

        $queryString = http_build_query([
            'width' => $params['width'] ?? $config['width'],
            'height' => $params['height'] ?? $config['height'],
            'quality' => $params['quality'] ?? $config['quality'],
            'format' => $params['format'] ?? $config['format'],
        ]);

        return "{$baseUrl}?{$queryString}";
    }

    private function getFallbackUrl(MediaFile $mediaFile, string $conversion): string
    {
        // Fallback to local storage if CDN is not available
        $tenantPath = $mediaFile->getTenantPath();
        return $this->storage->disk($mediaFile->disk)->url($tenantPath);
    }

    public function uploadToCdn(MediaFile $mediaFile): void
    {
        if (!config('cdn.enabled')) {
            $this->log->info('CDN is disabled, skipping upload', ['media_id' => $mediaFile->id]);
            return;
        }

        $cdnProvider = config('cdn.provider');

        try {
            match ($cdnProvider) {
                'cloudflare' => $this->uploadToCloudflare($mediaFile),
                'bunny' => $this->uploadToBunny($mediaFile),
                default => $this->log->warning('Unknown CDN provider', ['provider' => $cdnProvider]),
            };
        } catch (\Exception $e) {
            $this->log->error('Failed to upload to CDN', [
                'media_id' => $mediaFile->id,
                'provider' => $cdnProvider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function uploadToCloudflare(MediaFile $mediaFile): void
    {
        $apiKey = config('cdn.cloudflare.api_key');
        $accountId = config('cdn.cloudflare.account_id');
        $tenantPath = $mediaFile->getTenantPath();
        $localPath = $this->storage->disk($mediaFile->disk)->path($tenantPath);

        $client = new \GuzzleHttp\Client();

        $response = $client->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/images/v1", [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($localPath, 'r'),
                    'filename' => $mediaFile->originalFileName,
                ],
                [
                    'name' => 'metadata',
                    'contents' => json_encode([
                        'tenant_id' => $mediaFile->tenantId,
                        'model_type' => $mediaFile->modelType,
                        'model_id' => $mediaFile->modelId,
                        'collection' => $mediaFile->collection->value,
                        'original_filename' => $mediaFile->originalFileName,
                    ]),
                ],
                [
                    'name' => 'requireSignedURLs',
                    'contents' => config('cdn.cloudflare.require_signed_urls', false) ? 'true' : 'false',
                ],
            ],
            'timeout' => 60,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!$result['success']) {
            throw new \RuntimeException('Cloudflare upload failed: ' . json_encode($result['errors']));
        }

        $cdnUrl = $result['result']['variants'][0];
        $imageId = $result['result']['id'];

        // Create variants for different sizes
        $this->createCloudflareVariants($accountId, $apiKey, $imageId, $mediaFile->collection);

        $this->mediaRepository->markAsOptimized(
            $mediaFile->id,
            $cdnUrl,
            ['cdn_image_id' => $imageId, 'cdn_provider' => 'cloudflare']
        );

        $this->log->info('Successfully uploaded to Cloudflare', [
            'media_id' => $mediaFile->id,
            'image_id' => $imageId,
            'cdn_url' => $cdnUrl,
        ]);
    }

    private function createCloudflareVariants(string $accountId, string $apiKey, string $imageId, $collection): void
    {
        $variants = match ($collection->value) {
            'avatar' => ['avatar' => 'w=512,h=512,fit=crop'],
            'medical' => ['medical' => 'w=1600,h=1200,fit=max,quality=92'],
            default => [
                'thumbnail' => 'w=300,h=300,fit=crop',
                'medium' => 'w=1200,h=800,fit=max',
                'large' => 'w=2048,h=2048,fit=max',
            ],
        };

        $client = new \GuzzleHttp\Client();

        foreach ($variants as $variantName => $variantConfig) {
            try {
                $client->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/images/v1/{$imageId}/variants/{$variantName}", [
                    'headers' => [
                        'Authorization' => "Bearer {$apiKey}",
                    ],
                    'json' => [
                        'options' => $variantConfig,
                    ],
                ]);
            } catch (\Exception $e) {
                $this->log->warning('Failed to create Cloudflare variant', [
                    'variant' => $variantName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function uploadToBunny(MediaFile $mediaFile): void
    {
        $storageZoneName = config('cdn.bunny.storage_zone_name');
        $accessKey = config('cdn.bunny.access_key');
        $tenantPath = $mediaFile->getTenantPath();
        $localPath = $this->storage->disk($mediaFile->disk)->path($tenantPath);

        $client = new \GuzzleHttp\Client();

        $cdnPath = str_replace('tenant/', '', $tenantPath);
        $url = "https://storage.bunnycdn.com/{$storageZoneName}/{$cdnPath}";

        $response = $client->put($url, [
            'headers' => [
                'AccessKey' => $accessKey,
                'Content-Type' => $mediaFile->mimeType->value,
            ],
            'body' => fopen($localPath, 'r'),
            'timeout' => 60,
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \RuntimeException('Bunny CDN upload failed');
        }

        $cdnUrl = "https://{$storageZoneName}.b-cdn.net/{$cdnPath}";

        $this->mediaRepository->markAsOptimized(
            $mediaFile->id,
            $cdnUrl,
            ['cdn_provider' => 'bunny']
        );

        $this->log->info('Successfully uploaded to Bunny CDN', [
            'media_id' => $mediaFile->id,
            'cdn_url' => $cdnUrl,
        ]);
    }

    public function deleteFromCdn(MediaFile $mediaFile): void
    {
        if (!config('cdn.enabled') || $mediaFile->cdnUrl === null) {
            return;
        }

        $cdnProvider = config('cdn.provider');

        try {
            match ($cdnProvider) {
                'cloudflare' => $this->deleteFromCloudflare($mediaFile),
                'bunny' => $this->deleteFromBunny($mediaFile),
                default => null,
            };
        } catch (\Exception $e) {
            $this->log->error('Failed to delete from CDN', [
                'media_id' => $mediaFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteFromCloudflare(MediaFile $mediaFile): void
    {
        $imageId = $mediaFile->metadata['cdn_image_id'] ?? null;

        if ($imageId === null) {
            return;
        }

        $apiKey = config('cdn.cloudflare.api_key');
        $accountId = config('cdn.cloudflare.account_id');

        $client = new \GuzzleHttp\Client();

        $client->delete("https://api.cloudflare.com/client/v4/accounts/{$accountId}/images/v1/{$imageId}", [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
        ]);

        $this->log->info('Successfully deleted from Cloudflare', ['media_id' => $mediaFile->id]);
    }

    private function deleteFromBunny(MediaFile $mediaFile): void
    {
        $storageZoneName = config('cdn.bunny.storage_zone_name');
        $accessKey = config('cdn.bunny.access_key');
        $cdnPath = parse_url($mediaFile->cdnUrl, PHP_URL_PATH);

        if ($cdnPath === false) {
            return;
        }

        $cdnPath = ltrim($cdnPath, '/');
        $url = "https://storage.bunnycdn.com/{$storageZoneName}/{$cdnPath}";

        $client = new \GuzzleHttp\Client();

        $client->delete($url, [
            'headers' => [
                'AccessKey' => $accessKey,
            ],
        ]);

        $this->log->info('Successfully deleted from Bunny CDN', ['media_id' => $mediaFile->id]);
    }

    public function getSignedUrl(MediaFile $mediaFile, int $ttlMinutes = 15): string
    {
        if (!config('cdn.enabled')) {
            return $this->getPresignedUrlFromStorage($mediaFile, $ttlMinutes);
        }

        $cdnProvider = config('cdn.provider');

        return match ($cdnProvider) {
            'cloudflare' => $this->getCloudflareSignedUrl($mediaFile, $ttlMinutes),
            'bunny' => $this->getBunnySignedUrl($mediaFile, $ttlMinutes),
            default => $this->getPresignedUrlFromStorage($mediaFile, $ttlMinutes),
        };
    }

    private function getCloudflareSignedUrl(MediaFile $mediaFile, int $ttlMinutes): string
    {
        $imageId = $mediaFile->metadata['cdn_image_id'] ?? null;

        if ($imageId === null) {
            return $mediaFile->cdnUrl ?? $this->getPresignedUrlFromStorage($mediaFile, $ttlMinutes);
        }

        $apiKey = config('cdn.cloudflare.api_key');
        $accountId = config('cdn.cloudflare.account_id');
        $expires = now()->addMinutes($ttlMinutes)->timestamp;
        $token = hash_hmac('sha256', "{$imageId}{$expires}", $apiKey);

        return "https://{$accountId}.imagedelivery.net/{$imageId}/public?expires={$expires}&token={$token}";
    }

    private function getBunnySignedUrl(MediaFile $mediaFile, int $ttlMinutes): string
    {
        $storageZoneName = config('cdn.bunny.storage_zone_name');
        $accessKey = config('cdn.bunny.access_key');
        $path = parse_url($mediaFile->cdnUrl, PHP_URL_PATH);

        if ($path === false) {
            return $this->getPresignedUrlFromStorage($mediaFile, $ttlMinutes);
        }

        $expires = now()->addMinutes($ttlMinutes)->timestamp;
        $token = hash_hmac('sha256', "{$path}{$expires}", $accessKey);

        return "https://{$storageZoneName}.b-cdn.net{$path}?token={$token}&expires={$expires}";
    }

    private function getPresignedUrlFromStorage(MediaFile $mediaFile, int $ttlMinutes): string
    {
        $tenantPath = $mediaFile->getTenantPath();

        if (config('filesystems.disks.' . $mediaFile->disk . '.driver') === 's3') {
            return $this->storage->disk($mediaFile->disk)
                ->temporaryUrl($tenantPath, now()->addMinutes($ttlMinutes));
        }

        return $this->storage->disk($mediaFile->disk)->url($tenantPath);
    }
}
