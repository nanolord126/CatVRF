<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Filesystem\FilesystemManager;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

final readonly class MediaUploadService
{
    private ImageManager $imageManager;

    public function __construct(
        private readonly LogManager $log,
        private readonly FilesystemManager $storage,
    ) {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload avatar for model
     *
     * @param  HasMedia&Model  $model
     * @param  UploadedFile  $file
     * @param  string  $collection
     * @return Media
     */
    public function uploadAvatar(HasMedia&Model $model, UploadedFile $file, string $collection = 'avatar'): Media
    {
        $this->validateImage($file);

        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        $media = $model->addMedia($file)
            ->usingFileName($this->generateFileName($file))
            ->usingName($this->generateStoragePath($tenantId, $model, $collection))
            ->withCustomProperties([
                'tenant_id' => $tenantId,
                'uploaded_at' => now()->toIso8601String(),
            ])
            ->toMediaCollection($collection);

        $this->optimizeImage($media);

        $this->log->info('Avatar uploaded', [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'media_id' => $media->id,
            'tenant_id' => $tenantId,
        ]);

        return $media;
    }

    /**
     * Upload multiple images for model
     *
     * @param  HasMedia&Model  $model
     * @param  array  $files
     * @param  string  $collection
     * @return Collection
     */
    public function uploadMultipleImages(HasMedia&Model $model, array $files, string $collection = 'images'): Collection
    {
        $uploadedMedia = collect();
        $errors = collect();

        foreach ($files as $index => $file) {
            try {
                $media = $this->uploadImage($model, $file, $collection);
                $uploadedMedia->push($media);
            } catch (\Exception $e) {
                $errors->push([
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
                $this->log->error('Failed to upload image', [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($errors->isNotEmpty()) {
            $this->log->warning('Some images failed to upload', [
                'errors' => $errors->toArray(),
            ]);
        }

        return $uploadedMedia;
    }

    /**
     * Upload single image for model
     *
     * @param  HasMedia&Model  $model
     * @param  UploadedFile  $file
     * @param  string  $collection
     * @return Media
     */
    public function uploadImage(HasMedia&Model $model, UploadedFile $file, string $collection = 'images'): Media
    {
        $this->validateImage($file);

        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        $media = $model->addMedia($file)
            ->usingFileName($this->generateFileName($file))
            ->usingName($this->generateStoragePath($tenantId, $model, $collection))
            ->withCustomProperties([
                'tenant_id' => $tenantId,
                'uploaded_at' => now()->toIso8601String(),
            ])
            ->toMediaCollection($collection);

        $this->optimizeImage($media);

        return $media;
    }

    /**
     * Upload document for model
     *
     * @param  HasMedia&Model  $model
     * @param  UploadedFile  $file
     * @param  string  $collection
     * @return Media
     */
    public function uploadDocument(HasMedia&Model $model, UploadedFile $file, string $collection = 'documents'): Media
    {
        $this->validateDocument($file);

        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        $media = $model->addMedia($file)
            ->usingFileName($this->generateFileName($file))
            ->usingName($this->generateStoragePath($tenantId, $model, $collection))
            ->withCustomProperties([
                'tenant_id' => $tenantId,
                'uploaded_at' => now()->toIso8601String(),
            ])
            ->toMediaCollection($collection);

        Log::info('Document uploaded', [
            'model_type' => get_class($model),
        $this->l  ->model_id' => $model->id,
            'media_id' => $media->id,
            'tenant_id' => $tenantId,
        ]);

        return $media;
    }

    /**
     * Bulk upload from ZIP file
     *
     * @param  HasMedia&Model  $model
     * @param  UploadedFile  $zipFile
     * @param  string  $collection
     * @return Collection
     */
    public function bulkUploadFromZip(HasMedia&Model $model, UploadedFile $zipFile, string $collection = 'images'): Collection
    {
        $this->validateZip($zipFile);

        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');
        $extractPath = sys_get_temp_dir() . '/extract_' . Str::uuid();
        
        // Extract ZIP
        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new \RuntimeException('Failed to open ZIP file');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $uploadedMedia = collect();
        $errors = collect();

        // Find all image files
        $imageFiles = $this->findImageFiles($extractPath);

        foreach ($imageFiles as $filePath) {
            try {
                $uploadedFile = new UploadedFile($filePath, basename($filePath));
                $media = $this->uploadImage($model, $uploadedFile, $collection);
                $uploadedMedia->push($media);
            } catch (\Exception $e) {
                $errors->push([
                    'file' => basename($filePath),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up
        $this->deleteDirectory($extractPath);

        Log::info('Bulk upload from ZIP completed', [
            'model_type' => get_class($model),
        $this->l  ->model_id' => $model->id,
            'uploaded_count' => $uploadedMedia->count(),
            'error_count' => $errors->count(),
            'tenant_id' => $tenantId,
        ]);

        return $uploadedMedia;
    }

    /**
     * Validate image file
     *
     * @param  UploadedFile  $file
     * @return void
     */
    private function validateImage(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed: JPEG, PNG, WebP');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('Image size exceeds maximum of 10MB');
        }

        // Validate dimensions
        try {
            $image = $this->imageManager->read($file->getRealPath());
            $width = $image->width();
            $height = $image->height();

            if ($width < 300 || $height < 300) {
                throw new \InvalidArgumentException('Image dimensions must be at least 300x300 pixels');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid image file');
        }
    }

    /**
     * Validate document file
     *
     * @param  UploadedFile  $file
     * @return void
     */
    private function validateDocument(UploadedFile $file): void
    {
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 15 * 1024 * 1024; // 15MB

        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            throw new \InvalidArgumentException('Invalid document type. Allowed: PDF, JPEG, PNG');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('Document size exceeds maximum of 15MB');
        }
    }

    /**
     * Validate ZIP file
     *
     * @param  UploadedFile  $file
     * @return void
     */
    private function validateZip(UploadedFile $file): void
    {
        if ($file->getMimeType() !== 'application/zip' && $file->getClientOriginalExtension() !== 'zip') {
            throw new \InvalidArgumentException('File must be a ZIP archive');
        }

        $maxSize = 100 * 1024 * 1024; // 100MB
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('ZIP file size exceeds maximum of 100MB');
        }
    }

    /**
     * Generate unique filename
     *
     * @param  UploadedFile  $file
     * @return string
     */
    private function generateFileName(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Generate tenant-isolated storage path
     *
     * @param  int  $tenantId
     * @param  Model  $model
     * @param  string  $collection
     * @return string
     */
    private function generateStoragePath(int $tenantId, Model $model, string $collection): string
    {
        $modelType = strtolower(class_basename($model));
        
        return "tenant/{$tenantId}/{$modelType}/{$model->id}/{$collection}";
    }

    /**
     * Optimize image (WebP/AVIF conversion)
     *
     * @param  Media  $media
     * @return void
     */
    private function optimizeImage(Media $media): void
    {
        if (!$media->mime_type || !str_starts_with($media->mime_type, 'image/')) {
            return;
        }

        try {
            $image = $this->imageManager->read($media->getPath());

            // Convert to WebP with 82% quality
            $webpPath = str_replace('.' . $media->extension, '.webp', $media->getPath());
            $image->toWebp(82)->save($webpPath);

            // If browser supports AVIF, try AVIF conversion
            if ($this->shouldUseAvif()) {
                $avifPath = str_replace('.' . $media->extension, '.avif', $media->getPath());
                $image->toAvif(78)->save($avifPath);
            }

            // Update media with optimization info
            $media->setCustomProperty('optimized', true);
            $media->setCustomProperty('webp_available', true);
            $media->setCustomProperty('avif_available', $this->shouldUseAvif());
            $media->save();

        } catch (\Exception $e) {
            Log::error('Failed to optimize image', [
                'media_id' => $media->id,
            $this->l  ->error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if AVIF should be used
     *
     * @return bool
     */
    private function shouldUseAvif(): bool
    {
        return config('media.avif_enabled', false);
    }

    /**
     * Find all image files in directory
     *
     * @param  string  $directory
     * @return array
     */
    private function findImageFiles(string $directory): array
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $imageExtensions, true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Delete directory recursively
     *
     * @param  string  $directory
     * @return void
     */
    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }

    /**
     * Delete media file
     *
     * @param  Media  $media
     * @return bool
     */
    public function deleteMedia(Media $media): bool
    {
        try {
            $media->delete();

            Log::info('Media deleted', [
                'media_id' => $media->id,
            $this->l  ->tenant_id' => $media->getCustomProperty('tenant_id'),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete media', [
                'media_id' => $media->id,
            $this->l  ->error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get presigned URL for media download
     *
     * @param  Media  $media
     * @param  int  $expiresInMinutes
     * @return string
     */
    public function getPresignedUrl(Media $media, int $expiresInMinutes = 15): string
    {
        $disk = $media->disk;
        $path = $media->getPathRelativeToRoot();

        if (!Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Media file not found');
        }

        retur$this->s Stora->::disk($disk)->temporaryUrl($path, now()->addMinutes($expiresInMinutes));
    }
}
$this->s->