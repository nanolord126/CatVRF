<?php

declare(strict_types=1);

namespace Modules\Media\Domain\Traits;

use Modules\Media\Application\Services\CdnMediaService;
use Modules\Media\Application\Services\MediaUploadService;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;

trait HasMediaTrait
{
    public function getMedia(string $collection): array
    {
        $repository = app(MediaRepositoryInterface::class);
        return $repository->findByCollection(
            static::class,
            (string) $this->getKey(),
            MediaCollectionType::from($collection),
        );
    }

    public function getMediaUrl(string $collection, string $conversion = 'medium'): ?string
    {
        $mediaFiles = $this->getMedia($collection);

        if (empty($mediaFiles)) {
            return null;
        }

        $cdnService = app(CdnMediaService::class);
        return $cdnService->getOptimizedUrl($mediaFiles[0], $conversion);
    }

    public function addMediaFromRequest(
        \Illuminate\Http\Request $request,
        string $collection,
        ?string $fileKey = 'file',
    ): ?MediaFile {
        if (!$request->hasFile($fileKey)) {
            return null;
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file($fileKey);

        $uploadService = app(MediaUploadService::class);
        $dto = new \Modules\Media\Domain\DTOs\UploadMediaDTO(
            modelType: static::class,
            modelId: (string) $this->getKey(),
            collection: MediaCollectionType::from($collection),
            optimize: true,
            generateConversions: true,
        );

        return $uploadService->uploadSingle($file, $dto);
    }

    public function addAvatar(\Illuminate\Http\UploadedFile $file): MediaFile
    {
        $uploadService = app(MediaUploadService::class);
        return $uploadService->uploadAvatar($this, $file);
    }

    public function addDocument(\Illuminate\Http\UploadedFile $file): MediaFile
    {
        $uploadService = app(MediaUploadService::class);
        return $uploadService->uploadDocument($this, $file);
    }

    public function addBeforeAfterPhotos(
        \Illuminate\Http\UploadedFile $before,
        \Illuminate\Http\UploadedFile $after,
    ): array {
        $uploadService = app(MediaUploadService::class);
        return $uploadService->uploadBeforeAfterPhotos($this, $before, $after);
    }

    public function deleteMedia(string $mediaId): void
    {
        $repository = app(MediaRepositoryInterface::class);
        $cdnService = app(CdnMediaService::class);

        $mediaFile = $repository->findById($mediaId);

        if ($mediaFile !== null) {
            $cdnService->deleteFromCdn($mediaFile);
            $repository->delete($mediaId);
        }
    }

    public function syncMedia(array $mediaIds, string $collection): void
    {
        $repository = app(MediaRepositoryInterface::class);
        $cdnService = app(CdnMediaService::class);

        $existingMedia = $repository->findByCollection(
            static::class,
            (string) $this->getKey(),
            MediaCollectionType::from($collection),
        );

        $existingIds = array_map(fn (MediaFile $m) => $m->id, $existingMedia);
        $idsToDelete = array_diff($existingIds, $mediaIds);

        foreach ($idsToDelete as $id) {
            $mediaFile = $repository->findById($id);
            if ($mediaFile !== null) {
                $cdnService->deleteFromCdn($mediaFile);
                $repository->delete($id);
            }
        }
    }

    // ==================== Vertical-specific helper methods ====================

    /**
     * Add room photos (Hotels vertical)
     */
    public function addRoomPhotos(array $photos): array
    {
        return $this->addMultipleMedia($photos, 'gallery');
    }

    /**
     * Add product images (Flowers, Fashion verticals)
     */
    public function addProductImages(array $images): array
    {
        return $this->addMultipleMedia($images, 'gallery');
    }

    /**
     * Add food photos (Restaurant vertical)
     */
    public function addFoodPhotos(array $photos): array
    {
        return $this->addMultipleMedia($photos, 'gallery');
    }

    /**
     * Add property photos (RealEstate vertical)
     */
    public function addPropertyPhotos(array $photos): array
    {
        return $this->addMultipleMedia($photos, 'gallery');
    }

    /**
     * Add property virtual tour (RealEstate vertical)
     */
    public function addVirtualTour(\Illuminate\Http\UploadedFile $video): MediaFile
    {
        $uploadService = app(MediaUploadService::class);
        $dto = new \Modules\Media\Domain\DTOs\UploadMediaDTO(
            modelType: static::class,
            modelId: (string) $this->getKey(),
            collection: MediaCollectionType::VIDEOS,
            optimize: false,
            generateConversions: true,
        );

        return $uploadService->uploadSingle($video, $dto);
    }

    /**
     * Add AR model (RealEstate vertical)
     */
    public function addARModel(\Illuminate\Http\UploadedFile $model): MediaFile
    {
        $uploadService = app(MediaUploadService::class);
        $dto = new \Modules\Media\Domain\DTOs\UploadMediaDTO(
            modelType: static::class,
            modelId: (string) $this->getKey(),
            collection: MediaCollectionType::DOCUMENTS,
            optimize: false,
            generateConversions: false,
        );

        return $uploadService->uploadSingle($model, $dto);
    }

    /**
     * Add medical documents (VetGrooming vertical)
     */
    public function addMedicalDocuments(array $documents): array
    {
        return $this->addMultipleMedia($documents, 'documents');
    }

    /**
     * Add certification documents (BeautyMasters vertical)
     */
    public function addCertificationDocuments(array $documents): array
    {
        return $this->addMultipleMedia($documents, 'documents');
    }

    /**
     * Add portfolio photos (BeautyMasters vertical)
     */
    public function addPortfolioPhotos(array $photos): array
    {
        return $this->addMultipleMedia($photos, 'gallery');
    }

    /**
     * Add flower photos (Flowers vertical)
     */
    public function addFlowerPhotos(array $photos): array
    {
        return $this->addMultipleMedia($photos, 'gallery');
    }

    /**
     * Add bouquet photos (Flowers vertical)
     */
    public function addBouquetPhotos(array $photos): array
    {
        return $this->addMultipleMedia($photos, 'gallery');
    }

    /**
     * Helper to add multiple media files
     */
    protected function addMultipleMedia(array $files, string $collection): array
    {
        $uploadService = app(MediaUploadService::class);
        $dto = new \Modules\Media\Domain\DTOs\UploadMediaDTO(
            modelType: static::class,
            modelId: (string) $this->getKey(),
            collection: MediaCollectionType::from($collection),
            optimize: true,
            generateConversions: true,
        );

        return $uploadService->uploadMultiple($files, $dto);
    }
}
