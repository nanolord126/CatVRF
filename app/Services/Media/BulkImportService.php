<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class BulkImportService
{
    use WithAuditLogging;

    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
        private readonly MediaValidationService $mediaValidationService,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Bulk import items with images
     *
     * @param  string  $modelClass
     * @param  array  $items
     * @param  array  $files
     * @param  string  $collection
     * @return array
     */
    public function bulkImport(string $modelClass, array $items, array $files = [], string $collection = 'images'): array
    {
        $results = [
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $this->db->beginTransaction();

        try {
            foreach ($items as $index => $item) {
                try {
                    $model = $this->createOrUpdateModel($modelClass, $item);

                    // Attach images if provided
                    if (isset($files[$index]) && is_array($files[$index])) {
                        foreach ($files[$index] as $file) {
                            if ($file instanceof UploadedFile) {
                                $this->mediaUploadService->uploadImage($model, $file, $collection);
                            }
                        }
                    }

                    if ($model->wasRecentlyCreated) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'index' => $index,
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ];
                    $this->log->error('Bulk import item failed', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($results['failed'] > 0) {
                $this->db->rollBack();
                $results['success'] = false;
                $results['message'] = 'Bulk import failed due to errors';
            } else {
                $this->db->commit();
                $results['message'] = 'Bulk import completed successfully';
            }

            $this->log->info('Bulk import completed', $results);

            return $results;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log->error('Bulk import transaction failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Bulk import failed: ' . $e->getMessage(),
                'created' => 0,
                'updated' => 0,
                'failed' => count($items),
                'errors' => [['error' => $e->getMessage()]],
            ];
        }
    }

    /**
     * Import from CSV
     *
     * @param  string  $modelClass
     * @param  string  $csvPath
     * @param  array  $fileMap
     * @param  string  $collection
     * @return array
     */
    public function importFromCsv(string $modelClass, string $csvPath, array $fileMap = [], string $collection = 'images'): array
    {
        $items = $this->parseCsv($csvPath);
        $files = $this->mapFilesToItems($items, $fileMap);

        return $this->bulkImport($modelClass, $items, $files, $collection);
    }

    /**
     * Import from JSON
     *
     * @param  string  $modelClass
     * @param  string  $jsonPath
     * @param  array  $fileMap
     * @param  string  $collection
     * @return array
     */
    public function importFromJson(string $modelClass, string $jsonPath, array $fileMap = [], string $collection = 'images'): array
    {
        $json = file_get_contents($jsonPath);
        $items = json_decode($json, true);

        if (!is_array($items)) {
            throw new \InvalidArgumentException('Invalid JSON format');
        }

        $files = $this->mapFilesToItems($items, $fileMap);

        return $this->bulkImport($modelClass, $items, $files, $collection);
    }

    /**
     * Parse CSV file
     *
     * @param  string  $csvPath
     * @return array
     */
    private function parseCsv(string $csvPath): array
    {
        $items = [];
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Failed to open CSV file');
        }

        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $item = [];
            foreach ($headers as $index => $header) {
                $item[$header] = $row[$index] ?? null;
            }
            $items[] = $item;
        }

        fclose($handle);

        return $items;
    }

    /**
     * Map files to items by index
     *
     * @param  array  $items
     * @param  array  $fileMap
     * @return array
     */
    private function mapFilesToItems(array $items, array $fileMap): array
    {
        $files = [];

        foreach ($items as $index => $item) {
            $files[$index] = $fileMap[$index] ?? [];
        }

        return $files;
    }

    /**
     * Create or update model
     *
     * @param  string  $modelClass
     * @param  array  $data
     * @return Model
     */
    private function createOrUpdateModel(string $modelClass, array $data): Model
    {
        // Validate data
        $errors = $this->mediaValidationService->validateImportItem($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        // Check for existing model by ID or unique identifier
        $identifier = $data['id'] ?? $data['name'] ?? null;
        
        if ($identifier && isset($data['id'])) {
            $model = $modelClass::find($data['id']);
        } else {
            $model = null;
        }

        if ($model) {
            // Update existing
            $model->update($this->prepareModelData($data));
        } else {
            // Create new
            $model = $modelClass::create($this->prepareModelData($data));
        }

        return $model;
    }

    /**
     * Prepare model data
     *
     * @param  array  $data
     * @return array
     */
    private function prepareModelData(array $data): array
    {
        $prepared = [];

        foreach ($data as $key => $value) {
            // Skip non-model fields
            if (in_array($key, ['files', 'images'], true)) {
                continue;
            }

            $prepared[$key] = $value;
        }

        // Add tenant ID
        $prepared['tenant_id'] = tenant()?->id;

        return $prepared;
    }
}
