<?php

declare(strict_types=1);

namespace Modules\Media\Application\Services;

use Illuminate\Http\UploadedFile;
use Modules\Media\Domain\DTOs\MediaValidationResultDTO;
use Modules\Media\Domain\Exceptions\MediaUploadException;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaMimeType;

final readonly class MediaValidationService
{
    public function validateFile(UploadedFile $file, MediaCollectionType $collection): MediaValidationResultDTO
    {
        $errors = [];
        $warnings = [];

        // Validate MIME type
        try {
            $mimeType = MediaMimeType::fromString($file->getMimeType());
        } catch (\ValueError $e) {
            return MediaValidationResultDTO::invalid(['Invalid MIME type: ' . $file->getMimeType()]);
        }

        // Validate size based on collection type
        $maxSize = $this->getMaxSizeForCollection($collection);
        if ($file->getSize() > $maxSize) {
            $errors[] = "File size exceeds maximum of " . round($maxSize / 1024 / 1024, 2) . "MB";
        }

        // Validate image dimensions if applicable
        if ($mimeType->isImage() && $collection->isImageCollection()) {
            $dimensions = getimagesize($file->getPathname());
            if ($dimensions === false) {
                $errors[] = 'Could not read image dimensions';
            } else {
                [$width, $height] = $dimensions;
                if ($width < 300 || $height < 300) {
                    $errors[] = 'Image dimensions must be at least 300x300';
                }
            }
        }

        // Validate for medical collections
        if ($collection === MediaCollectionType::MEDICAL) {
            if (!$mimeType->isImage()) {
                $errors[] = 'Medical collection only accepts images';
            }
        }

        if (!empty($errors)) {
            return MediaValidationResultDTO::invalid($errors);
        }

        if (!empty($warnings)) {
            return MediaValidationResultDTO::withWarnings($warnings);
        }

        return MediaValidationResultDTO::valid();
    }

    private function getMaxSizeForCollection(MediaCollectionType $collection): int
    {
        return match ($collection) {
            MediaCollectionType::AVATAR,
            MediaCollectionType::IMAGES,
            MediaCollectionType::PRODUCTS,
            MediaCollectionType::SERVICES,
            MediaCollectionType::PORTFOLIO => 10 * 1024 * 1024, // 10MB
            MediaCollectionType::MEDICAL,
            MediaCollectionType::BEFORE_AFTER => 15 * 1024 * 1024, // 15MB
            MediaCollectionType::DOCUMENTS => 15 * 1024 * 1024, // 15MB
            MediaCollectionType::VIDEO_RECORDING => 500 * 1024 * 1024, // 500MB
            MediaCollectionType::VIDEO_THUMBNAIL => 10 * 1024 * 1024, // 10MB
        };
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, MediaValidationResultDTO>
     */
    public function validateMultiple(array $files, MediaCollectionType $collection): array
    {
        $results = [];

        foreach ($files as $index => $file) {
            $results[$index] = $this->validateFile($file, $collection);
        }

        return $results;
    }
}
