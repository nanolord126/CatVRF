<?php

declare(strict_types=1);

namespace Modules\Media\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Modules\Media\Domain\DTOs\MediaValidationResultDTO;
use Modules\Media\Domain\DTOs\UploadMediaDTO;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Exceptions\MediaUploadException;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaMimeType;
use Modules\Media\Domain\ValueObjects\MediaStatus;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * MediaUploadService — Сервис для загрузки медиа файлов
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Audit logging
 */
final readonly class MediaUploadService
{
    use WithAuditLogging;

    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_VIDEO_SIZE = 500 * 1024 * 1024; // 500MB
    private const MAX_DOCUMENT_SIZE = 15 * 1024 * 1024; // 15MB
    private const MIN_IMAGE_DIMENSIONS = 300;

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private MediaValidationService $validationService,
        private readonly AuditService $auditService,
        private readonly FilesystemManager $storage,
        private readonly LogManager $log,
    ) {}

    public function uploadSingle(UploadedFile $file, UploadMediaDTO $dto): MediaFile
    {
        $validationResult = $this->validationService->validateFile($file, $dto->collection);
        if (!$validationResult->valid) {
            throw MediaUploadException::invalidMimeType($file->getMimeType());
        }

        $mimeType = MediaMimeType::fromString($file->getMimeType());
        $disk = $dto->disk ?? config('media-library.disk_name');

        $mediaFile = MediaFile::create(
            modelType: $dto->modelType,
            modelId: $dto->modelId,
            collection: $dto->collection,
            mimeType: $mimeType,
            originalFileName: $file->getClientOriginalName(),
            disk: $disk,
            path: $this->generatePath($dto->modelType, $dto->modelId, $file->getClientOriginalExtension()),
            sizeBytes: $file->getSize(),
        );

        $this->mediaRepository->save($mediaFile);

        // Store file with tenant isolation
        $tenantPath = $mediaFile->getTenantPath();
        $this->storage->disk($disk)->put($tenantPath, file_get_contents($file->getPathname()));

        if ($dto->generateConversions) {
            $this->queueConversions($mediaFile);
        }

        return $mediaFile;
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, MediaFile>
     */
    public function uploadMultiple(array $files, UploadMediaDTO $dto): array
    {
        $mediaFiles = [];

        foreach ($files as $file) {
            try {
                $mediaFiles[] = $this->uploadSingle($file, $dto);
            } catch (MediaUploadException $e) {
                // Log error but continue with other files
                $this->log->warning('Failed to upload file', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $mediaFiles;
    }

    public function uploadAvatar(object $model, UploadedFile $file): MediaFile
    {
        $dto = new UploadMediaDTO(
            modelType: get_class($model),
            modelId: (string) $model->getKey(),
            collection: MediaCollectionType::AVATAR,
            optimize: true,
            generateConversions: true,
        );

        return $this->uploadSingle($file, $dto);
    }

    public function uploadDocument(object $model, UploadedFile $file): MediaFile
    {
        $dto = new UploadMediaDTO(
            modelType: get_class($model),
            modelId: (string) $model->getKey(),
            collection: MediaCollectionType::DOCUMENTS,
            optimize: false,
            generateConversions: false,
        );

        return $this->uploadSingle($file, $dto);
    }

    public function uploadBeforeAfterPhotos(object $model, UploadedFile $before, UploadedFile $after): array
    {
        $dto = new UploadMediaDTO(
            modelType: get_class($model),
            modelId: (string) $model->getKey(),
            collection: MediaCollectionType::BEFORE_AFTER,
            optimize: true,
            generateConversions: true,
            metadata: ['type' => 'before_after_pair'],
        );

        return [
            'before' => $this->uploadSingle($before, $dto),
            'after' => $this->uploadSingle($after, $dto),
        ];
    }

    private function generatePath(string $modelType, string $modelId, string $extension): string
    {
        $tenantId = tenant('id') ?? 'default';
        $uuid = Str::uuid();
        $shortModelType = str_replace('\\', '/', strtolower($modelType));

        return "tenant/{$tenantId}/media/{$shortModelType}/{$modelId}/{$uuid}.{$extension}";
    }

    private function queueConversions(MediaFile $mediaFile): void
    {
        if ($mediaFile->isImage()) {
            dispatch(new \Modules\Media\Application\Jobs\ProcessImageConversionsJob($mediaFile->id));
        } elseif ($mediaFile->isVideo()) {
            dispatch(new \Modules\Media\Application\Jobs\ProcessVideoConversionsJob($mediaFile->id));
        }
    }

    public function getPresignedUrl(MediaFile $mediaFile, int $ttlMinutes = 15): string
    {
        // Always use CDN for delivery
        if (config('cdn.enabled') && $mediaFile->cdnUrl !== null) {
            return $this->getCdnPresignedUrl($mediaFile, $ttlMinutes);
        }

        $tenantPath = $mediaFile->getTenantPath();

        if (config('filesystems.disks.' . $mediaFile->disk . '.driver') === 's3') {
            return $this->storage->disk($mediaFile->disk)
                ->temporaryUrl($tenantPath, now()->addMinutes($ttlMinutes));
        }

        return $this->storage->disk($mediaFile->disk)->url($tenantPath);
    }

    private function getCdnPresignedUrl(MediaFile $mediaFile, int $ttlMinutes): string
    {
        $cdnProvider = config('cdn.provider');

        return match ($cdnProvider) {
            'cloudflare' => $this->getCloudflarePresignedUrl($mediaFile, $ttlMinutes),
            'bunny' => $this->getBunnyPresignedUrl($mediaFile, $ttlMinutes),
            default => $mediaFile->cdnUrl,
        };
    }

    private function getCloudflarePresignedUrl(MediaFile $mediaFile, int $ttlMinutes): string
    {
        // Cloudflare Images signed URL generation
        $apiKey = config('cdn.cloudflare.api_key');
        $accountId = config('cdn.cloudflare.account_id');
        $imageId = $mediaFile->metadata['cdn_image_id'] ?? null;

        if ($imageId === null) {
            return $mediaFile->cdnUrl;
        }

        $expires = now()->addMinutes($ttlMinutes)->timestamp;
        $token = hash_hmac('sha256', "{$imageId}{$expires}", $apiKey);

        return "https://{$accountId}.imagedelivery.net/{$imageId}/public?expires={$expires}&token={$token}";
    }

    private function getBunnyPresignedUrl(MediaFile $mediaFile, int $ttlMinutes): string
    {
        // Bunny CDN signed URL generation
        $storageZoneName = config('cdn.bunny.storage_zone_name');
        $accessKey = config('cdn.bunny.access_key');
        $path = parse_url($mediaFile->cdnUrl, PHP_URL_PATH);

        $expires = now()->addMinutes($ttlMinutes)->timestamp;
        $token = hash_hmac('sha256', "{$path}{$expires}", $accessKey);

        return "https://{$storageZoneName}.b-cdn.net{$path}?token={$token}&expires={$expires}";
    }

    public function uploadToCdn(MediaFile $mediaFile): void
    {
        $cdnProvider = config('cdn.provider');

        match ($cdnProvider) {
            'cloudflare' => $this->uploadToCloudflare($mediaFile),
            'bunny' => $this->uploadToBunny($mediaFile),
            default => null,
        };
    }

    private function uploadToCloudflare(MediaFile $mediaFile): void
    {
        $apiKey = config('cdn.cloudflare.api_key');
        $accountId = config('cdn.cloudflare.account_id');
        $tenantPath = $mediaFile->getTenantPath();
        $localPath = $this->storage->disk($mediaFile->disk)->path($tenantPath);

        $client = new \GuzzleHttp\Client();

        try {
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
                        ]),
                    ],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['success']) {
                $cdnUrl = $result['result']['variants'][0];
                $imageId = $result['result']['id'];

                $this->mediaRepository->markAsOptimized(
                    $mediaFile->id,
                    $cdnUrl,
                    ['cdn_image_id' => $imageId]
                );
            }
        } catch (\Exception $e) {
            $this->log->error('Failed to upload to Cloudflare', [
                'media_id' => $mediaFile->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function uploadToBunny(MediaFile $mediaFile): void
    {
        $storageZoneName = config('cdn.bunny.storage_zone_name');
        $accessKey = config('cdn.bunny.access_key');
        $tenantPath = $mediaFile->getTenantPath();
        $localPath = $this->storage->disk($mediaFile->disk)->path($tenantPath);

        $client = new \GuzzleHttp\Client();

        try {
            $cdnPath = str_replace('tenant/', '', $tenantPath);
            $url = "https://storage.bunnycdn.com/{$storageZoneName}/{$cdnPath}";

            $response = $client->put($url, [
                'headers' => [
                    'AccessKey' => $accessKey,
                    'Content-Type' => $mediaFile->mimeType->value,
                ],
                'body' => fopen($localPath, 'r'),
            ]);

            $cdnUrl = "https://{$storageZoneName}.b-cdn.net/{$cdnPath}";

            $this->mediaRepository->markAsOptimized($mediaFile->id, $cdnUrl);
        } catch (\Exception $e) {
            $this->log->error('Failed to upload to Bunny CDN', [
                'media_id' => $mediaFile->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
