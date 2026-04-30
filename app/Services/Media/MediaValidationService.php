<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class MediaValidationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}
    /**
     * Validate bulk import data
     *
     * @param  array  $items
     * @return array
     */
    public function validateBulkImport(array $items): array
    {
        $errors = collect();

        foreach ($items as $index => $item) {
            $itemErrors = $this->validateImportItem($item);
            if (!empty($itemErrors)) {
                $errors->push([
                    'index' => $index,
                    'errors' => $itemErrors,
                ]);
            }
        }

        return $errors->toArray();
    }

    /**
     * Validate single import item
     *
     * @param  array  $item
     * @return array
     */
    private function validateImportItem(array $item): array
    {
        $errors = [];

        if (empty($item['name'])) {
            $errors[] = 'Name is required';
        }

        if (!isset($item['price']) || !is_numeric($item['price'])) {
            $errors[] = 'Price must be a valid number';
        }

        if (!isset($item['duration_minutes']) || !is_numeric($item['duration_minutes'])) {
            $errors[] = 'Duration must be a valid number';
        }

        if (isset($item['duration_minutes']) && $item['duration_minutes'] < 0) {
            $errors[] = 'Duration cannot be negative';
        }

        return $errors;
    }

    /**
     * Validate uploaded files
     *
     * @param  array  $files
     * @return array
     */
    public function validateUploadedFiles(array $files): array
    {
        $errors = collect();
        $maxFiles = 50;

        if (count($files) > $maxFiles) {
            $errors->push("Maximum {$maxFiles} files allowed per upload");
        }

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                $errors->push("File at index {$index} is not a valid uploaded file");
                continue;
            }

            try {
                $this->validateImageFile($file);
            } catch (\InvalidArgumentException $e) {
                $errors->push("File at index {$index} ({$file->getClientOriginalName()}): {$e->getMessage()}");
            }
        }

        return $errors->toArray();
    }

    /**
     * Validate image file
     *
     * @param  UploadedFile  $file
     * @return void
     */
    public function validateImageFile(UploadedFile $file): void
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
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        [$width, $height] = $imageInfo;
        if ($width < 300 || $height < 300) {
            throw new \InvalidArgumentException('Image dimensions must be at least 300x300 pixels');
        }
    }

    /**
     * Validate document file
     *
     * @param  UploadedFile  $file
     * @return void
     */
    public function validateDocumentFile(UploadedFile $file): void
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
     * Format validation errors for response
     *
     * @param  array  $errors
     * @return array
     */
    public function formatErrors(array $errors): array
    {
        return [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'error_count' => count($errors),
        ];
    }
}
