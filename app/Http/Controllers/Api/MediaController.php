<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Gate;

final class MediaController
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
        private readonly MediaValidationService $mediaValidationService
    ) {
    }

    /**
     * Upload single image
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|image|max:10240',
            'collection' => 'string|max:50',
        ]);

        try {
            $model = $this->getModelFromRequest($request);
            $file = $request->file('file');
            $collection = $request->input('collection', 'images');

            $media = $this->mediaUploadService->uploadImage($model, $file, $collection);

            return response()->json([
                'success' => true,
                'media' => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumbnail_url' => $media->getUrl('thumb'),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultipleImages(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:50',
            'files.*' => 'file|image|max:10240',
            'collection' => 'string|max:50',
        ]);

        try {
            $model = $this->getModelFromRequest($request);
            $files = $request->file('files');
            $collection = $request->input('collection', 'images');

            $errors = $this->mediaValidationService->validateUploadedFiles($files);

            if (!empty($errors)) {
                return response()->json($this->mediaValidationService->formatErrors($errors), 422);
            }

            $mediaCollection = $this->mediaUploadService->uploadMultipleImages($model, $files, $collection);

            return response()->json([
                'success' => true,
                'uploaded_count' => $mediaCollection->count(),
                'media' => $mediaCollection->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumbnail_url' => $media->getUrl('thumb'),
                    'size' => $media->size,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload document
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360',
            'collection' => 'string|max:50',
        ]);

        try {
            $model = $this->getModelFromRequest($request);
            $file = $request->file('file');
            $collection = $request->input('collection', 'documents');

            $media = $this->mediaUploadService->uploadDocument($model, $file, $collection);

            return response()->json([
                'success' => true,
                'media' => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk upload from ZIP
     */
    public function bulkUploadZip(Request $request): JsonResponse
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:102400',
            'collection' => 'string|max:50',
        ]);

        try {
            $model = $this->getModelFromRequest($request);
            $zipFile = $request->file('zip_file');
            $collection = $request->input('collection', 'images');

            $mediaCollection = $this->mediaUploadService->bulkUploadFromZip($model, $zipFile, $collection);

            return response()->json([
                'success' => true,
                'uploaded_count' => $mediaCollection->count(),
                'media' => $mediaCollection->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumbnail_url' => $media->getUrl('thumb'),
                    'size' => $media->size,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download media with presigned URL
     */
    public function download(Request $request, string $mediaId): JsonResponse
    {
        try {
            $media = Media::findOrFail($mediaId);

            $this->authorizeMediaAccess($media);

            $presignedUrl = $this->mediaUploadService->getPresignedUrl($media, 15);

            return response()->json([
                'success' => true,
                'url' => $presignedUrl,
                'expires_in_minutes' => 15,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Delete media
     */
    public function delete(Request $request, string $mediaId): JsonResponse
    {
        try {
            $media = Media::findOrFail($mediaId);

            $this->authorizeMediaAccess($media);

            $this->mediaUploadService->deleteMedia($media);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get model from request
     */
    private function getModelFromRequest(Request $request)
    {
        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');

        if (!$modelType || !$modelId) {
            throw new \InvalidArgumentException('model_type and model_id are required');
        }

        $model = $modelType::findOrFail($modelId);

        if (!method_exists($model, 'addMedia')) {
            throw new \InvalidArgumentException('Model does not support media uploads');
        }

        return $model;
    }

    /**
     * Authorize media access
     */
    private function authorizeMediaAccess(Media $media): void
    {
        $tenantId = $media->getCustomProperty('tenant_id');
        $currentTenantId = tenant()?->id;

        if ($tenantId && $currentTenantId && $tenantId !== $currentTenantId) {
            abort(403, 'Unauthorized access to media');
        }

        // Additional authorization checks can be added here
        if (!Gate::allows('view', $media->model)) {
            abort(403, 'Unauthorized access to media');
        }
    }
}
