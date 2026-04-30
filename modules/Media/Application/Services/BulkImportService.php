<?php

declare(strict_types=1);

namespace Modules\Media\Application\Services;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use Modules\Media\Domain\DTOs\BulkImportDTO;
use Modules\Media\Domain\DTOs\MediaValidationResultDTO;
use Modules\Media\Domain\DTOs\UploadMediaDTO;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Exceptions\BulkImportException;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaMimeType;
use Modules\Media\Domain\ValueObjects\MediaStatus;
use Throwable;

/**
 * BulkImportService — Сервис для массового импорта медиа
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class BulkImportService
{
    public function __construct(
        private MediaUploadService $uploadService,
        private MediaValidationService $validationService,
        private DatabaseManager $db,
        private LogManager $log,
        private FilesystemManager $storage,
    ) {
    }

    /**
     * @return array{success: int, failed: int, errors: array<string, mixed>}
     */
    public function importFromJson(BulkImportDTO $dto): array
    {
        if ($dto->validateOnly || $dto->dryRun) {
            return $this->validateImport($dto);
        }

        return $this->executeImport($dto);
    }

    /**
     * @return array{success: int, failed: int, errors: array<string, mixed>}
     */
    public function importFromZip(UploadedFile $zipFile, string $vertical): array
    {
        $tempPath = $zipFile->storeAs('temp/imports', $zipFile->getClientOriginalName());
        $extractPath = storage_path('app/temp/extracted/' . Str::uuid());

        try {
            $zip = new \ZipArchive();
            if ($zip->open(storage_path('app/' . $tempPath)) !== true) {
                throw BulkImportException::zipExtractionFailed();
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Look for CSV file
            $csvFile = $this->findCsvFile($extractPath);
            if ($csvFile === null) {
                throw BulkImportException::invalidFormat('CSV', 'none found');
            }

            $items = $this->parseCsv($csvFile);
            $photosPath = dirname($csvFile) . '/photos';

            return $this->processItemsWithPhotos($items, $photosPath, $vertical);
        } finally {
            // Cleanup
            $this->storage->delete($tempPath);
            $this->deleteDirectory($extractPath);
        }
    }

    /**
     * @return array{success: int, failed: int, errors: array<string, mixed>}
     */
    private function validateImport(BulkImportDTO $dto): array
    {
        $errors = [];
        $failed = 0;
        $success = 0;

        foreach ($dto->items as $index => $item) {
            $validationResult = $this->validateItem($item);
            if (!$validationResult->valid) {
                $errors["item_{$index}"] = $validationResult->errors;
                $failed++;
            } else {
                $success++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{success: int, failed: int, errors: array<string, mixed>}
     */
    private function executeImport(BulkImportDTO $dto): array
    {
        $errors = [];
        $success = 0;
        $failed = 0;

        $this->db->beginTransaction();

        try {
            foreach ($dto->items as $index => $item) {
                try {
                    $this->processItem($item, $dto->vertical);
                    $success++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors["item_{$index}"] = [
                        'error' => $e->getMessage(),
                        'item' => $item,
                    ];
                    $this->log->error('Bulk import item failed', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'item' => $item,
                    ]);
                }
            }

            $this->db->commit();

            return [
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors,
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function validateItem(array $item): MediaValidationResultDTO
    {
        $errors = [];

        if (!isset($item['name']) || empty($item['name'])) {
            $errors[] = 'Name is required';
        }

        if (!isset($item['price'])) {
            $errors[] = 'Price is required';
        }

        if (!isset($item['category_id'])) {
            $errors[] = 'Category ID is required';
        }

        if (!empty($errors)) {
            return MediaValidationResultDTO::invalid($errors);
        }

        return MediaValidationResultDTO::valid();
    }

    /**
     * @param array<string, mixed> $item
     */
    private function processItem(array $item, string $vertical): void
    {
        // This will be implemented per vertical (Service, Product, etc.)
        // For now, it's a placeholder
        $modelClass = match ($vertical) {
            'vetgrooming' => \Modules\VetGrooming\Domain\Entities\Service::class,
            'beauty' => \Modules\BeautyMasters\Domain\Entities\Service::class,
            'marketplace' => \Modules\Marketplace\Domain\Entities\Product::class,
            default => throw new \InvalidArgumentException("Unknown vertical: {$vertical}"),
        };

        // Create or update model
        // Attach photos if provided
        if (isset($item['photos']) && is_array($item['photos'])) {
            foreach ($item['photos'] as $photo) {
                // Upload photo
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $csvPath): array
    {
        $items = [];
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw BulkImportException::invalidFormat('CSV', 'unreadable');
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw BulkImportException::invalidFormat('CSV', 'no headers');
        }

        while (($row = fgetcsv($handle)) !== false) {
            $item = array_combine($headers, $row);
            if ($item !== false) {
                $items[] = $item;
            }
        }

        fclose($handle);
        return $items;
    }

    private function findCsvFile(string $directory): ?string
    {
        $files = glob($directory . '/*.csv');
        return $files[0] ?? null;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function processItemsWithPhotos(array $items, string $photosPath, string $vertical): array
    {
        $errors = [];
        $success = 0;
        $failed = 0;

        foreach ($items as $index => $item) {
            try {
                $photoFiles = [];
                if (isset($item['photo_files'])) {
                    $photoNames = explode(',', $item['photo_files']);
                    foreach ($photoNames as $photoName) {
                        $photoPath = trim($photosPath . '/' . trim($photoName));
                        if (file_exists($photoPath)) {
                            $photoFiles[] = new UploadedFile(
                                $photoPath,
                                basename($photoPath),
                                mime_content_type($photoPath),
                                null,
                                true
                            );
                        }
                    }
                }

                $item['photos'] = $photoFiles;
                $this->processItem($item, $vertical);
                $success++;
            } catch (Throwable $e) {
                $failed++;
                $errors["item_{$index}"] = $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

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
}
