<?php

declare(strict_types=1);

namespace Modules\Media\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Media\Application\Services\BulkImportService;
use Modules\Media\Application\Services\CdnMediaService;
use Modules\Media\Application\Services\MediaUploadService;
use Modules\Media\Application\Services\MediaValidationService;
use Modules\Media\Domain\DTOs\BulkImportDTO;
use Modules\Media\Domain\DTOs\MediaValidationResultDTO;
use Modules\Media\Domain\DTOs\UploadMediaDTO;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\Exceptions\MediaUploadException;

final readonly class MediaController
{
    public function __construct(
        private MediaUploadService $uploadService,
        private MediaValidationService $validationService,
        private BulkImportService $bulkImportService,
        private CdnMediaService $cdnService,
        private MediaRepositoryInterface $mediaRepository,
    ) {
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'model_type' => 'required|string',
            'model_id' => 'required|string',
            'collection' => 'required|string|in:avatar,images,documents,medical,before_after,portfolio,products,services,video_recording,video_thumbnail',
            'optimize' => 'sometimes|boolean',
            'generate_conversions' => 'sometimes|boolean',
        ]);

        try {
            /** @var UploadedFile $file */
            $file = $request->file('file');

            $dto = new UploadMediaDTO(
                modelType: $request->input('model_type'),
                modelId: $request->input('model_id'),
                collection: MediaCollectionType::from($request->input('collection')),
                optimize: $request->input('optimize', true),
                generateConversions: $request->input('generate_conversions', true),
            );

            $mediaFile = $this->uploadService->uploadSingle($file, $dto);

            // Upload to CDN if enabled
            if (config('cdn.enabled') && config('cdn.auto_upload')) {
                $this->cdnService->uploadToCdn($mediaFile);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mediaFile->id,
                    'url' => $this->cdnService->getSignedUrl($mediaFile),
                    'original_file_name' => $mediaFile->originalFileName,
                    'size_bytes' => $mediaFile->sizeBytes,
                    'mime_type' => $mediaFile->mimeType->value,
                    'collection' => $mediaFile->collection->value,
                ],
            ], 201);
        } catch (MediaUploadException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload file',
            ], 500);
        }
    }

    public function uploadMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|min:1|max:50',
            'files.*' => 'file|max:102400',
            'model_type' => 'required|string',
            'model_id' => 'required|string',
            'collection' => 'required|string|in:avatar,images,documents,medical,before_after,portfolio,products,services,video_recording,video_thumbnail',
        ]);

        try {
            /** @var array<int, UploadedFile> $files */
            $files = $request->file('files');

            $dto = new UploadMediaDTO(
                modelType: $request->input('model_type'),
                modelId: $request->input('model_id'),
                collection: MediaCollectionType::from($request->input('collection')),
            );

            $mediaFiles = $this->uploadService->uploadMultiple($files, $dto);

            return response()->json([
                'success' => true,
                'data' => array_map(fn (MediaFile $file) => [
                    'id' => $file->id,
                    'url' => $this->cdnService->getSignedUrl($file),
                    'original_file_name' => $file->originalFileName,
                    'size_bytes' => $file->sizeBytes,
                ], $mediaFiles),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'vertical' => 'required|string|in:vetgrooming,beauty,marketplace',
            'items' => 'required|array',
            'zip_file' => 'sometimes|file|mimes:zip|max:102400',
            'validate_only' => 'sometimes|boolean',
            'dry_run' => 'sometimes|boolean',
        ]);

        try {
            $dto = new BulkImportDTO(
                vertical: $request->input('vertical'),
                items: $request->input('items'),
                validateOnly: $request->input('validate_only', false),
                dryRun: $request->input('dry_run', false),
            );

            if ($request->hasFile('zip_file')) {
                /** @var UploadedFile $zipFile */
                $zipFile = $request->file('zip_file');
                $result = $this->bulkImportService->importFromZip($zipFile, $dto->vertical);
            } else {
                $result = $this->bulkImportService->importFromJson($dto);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOptimizedUrl(Request $request, string $mediaId): JsonResponse
    {
        $mediaFile = $this->mediaRepository->findById($mediaId);

        if ($mediaFile === null) {
            return response()->json([
                'success' => false,
                'error' => 'Media file not found',
            ], 404);
        }

        $conversion = $request->input('conversion', 'medium');
        $params = $request->only(['width', 'height', 'quality', 'format']);

        $url = $this->cdnService->getOptimizedUrl($mediaFile, $conversion, $params);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'conversion' => $conversion,
            ],
        ]);
    }

    public function download(Request $request, string $mediaId): JsonResponse
    {
        $mediaFile = $this->mediaRepository->findById($mediaId);

        if ($mediaFile === null) {
            return response()->json([
                'success' => false,
                'error' => 'Media file not found',
            ], 404);
        }

        $url = $this->cdnService->getSignedUrl($mediaFile, $request->input('ttl', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $url,
                'expires_in_minutes' => $request->input('ttl', 15),
            ],
        ]);
    }

    public function delete(string $mediaId): JsonResponse
    {
        $mediaFile = $this->mediaRepository->findById($mediaId);

        if ($mediaFile === null) {
            return response()->json([
                'success' => false,
                'error' => 'Media file not found',
            ], 404);
        }

        $this->cdnService->deleteFromCdn($mediaFile);
        $this->mediaRepository->delete($mediaId);

        return response()->json([
            'success' => true,
            'message' => 'Media file deleted successfully',
        ]);
    }

    public function getByModel(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|string',
            'collection' => 'sometimes|string',
        ]);

        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');
        $collection = $request->input('collection');

        $mediaFiles = $collection !== null
            ? $this->mediaRepository->findByCollection(
                $modelType,
                $modelId,
                MediaCollectionType::from($collection)
            )
            : $this->mediaRepository->findByModel($modelType, $modelId);

        return response()->json([
            'success' => true,
            'data' => array_map(fn (MediaFile $file) => [
                'id' => $file->id,
                'url' => $this->cdnService->getSignedUrl($file),
                'original_file_name' => $file->originalFileName,
                'size_bytes' => $file->sizeBytes,
                'mime_type' => $file->mimeType->value,
                'collection' => $file->collection->value,
                'status' => $file->status->value,
            ], $mediaFiles),
        ]);
    }
}
