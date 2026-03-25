<?php

declare(strict_types=1);

namespace App\Domains\ThreeD\Services;

use App\Domains\ThreeD\Models\Model3D;
use App\Domains\ThreeD\Events\Model3DUploaded;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;

final class Model3DService
{
    private const string STORAGE_DISK = 'tenant-3d-models';
    private const int MAX_FILE_SIZE = 52428800;

    public function __construct(
        private readonly Model3DValidationService $validationService,
    ) {
    }

    /**
     * Сохранить загруженную 3D модель
     * SECURITY: Валидация, хеширование, дедупликация, вирусный скан
     * @throws Exception
     */
    public function storeModel(
        int $tenantId,
        UploadedFile $file,
        string $name,
        ?string $description,
        string $correlationId,
    ): Model3D {
        return $this->db->transaction(function () use ($tenantId, $file, $name, $description, $correlationId): Model3D {
            // SECURITY: Проверка размера
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                throw new Exception('Файл превышает лимит 50MB', 413);
            }

            // SECURITY: Валидация контента (не только расширение)
            if (!$this->validationService->isValidGltfOrGlb($file)) {
                throw new Exception('Неверный формат 3D модели', 415);
            }

            // SECURITY: Вирусный скан
            $scanResult = $this->validationService->scanForMalware($file, $correlationId);
            if (!$scanResult['safe']) {
                $this->log->channel('audit')->warning('3D модель не прошла сканирование на вирусы', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'reason' => $scanResult['reason'],
                ]);
                throw new Exception('Файл не прошёл проверку безопасности', 403);
            }

            // Вычисляем SHA-256 хеш для дедупликации
            $hash = hash_file('sha256', $file->getRealPath());

            // SECURITY: Проверка дедупликации (IDOR: не раскрываем UUID существующей)
            $existingModel = Model3D::withoutGlobalScopes()
                ->where('hash', $hash)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existingModel) {
                $this->log->channel('audit')->info('3D модель уже загружена (дедупликация)', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'model_id' => $existingModel->id,
                ]);
                throw new Exception('Эта модель уже была загружена', 409);
            }

            // Генерируем имя файла
            $fileName = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
            $storagePath = "tenants/{$tenantId}/models/{$fileName}";

            // Сохраняем файл в защищённое хранилище
            try {
                $this->storage->disk('private')->putFileAs(
                    "tenants/{$tenantId}/models",
                    $file,
                    $fileName,
                    'private'
                );
            } catch (Exception $e) {
                $this->log->channel('audit')->error('Ошибка при сохранении 3D файла', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                throw new Exception('Ошибка при сохранении файла', 500);
            }

            // Создаём запись в БД
            $model = Model3D::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'description' => $description,
                'file_path' => $storagePath,
                'model_type' => $this->detectModelType($file),
                'file_size' => $file->getSize(),
                'hash' => $hash,
                'status' => 'processing',
                'correlation_id' => $correlationId,
            ]);

            // Диспатчим событие для асинхронной обработки
            Model3DUploaded::dispatch($model, $correlationId);

            $this->log->channel('audit')->info('3D модель создана', [
                'model_id' => $model->id,
                'uuid' => $model->uuid,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'file_size' => $file->getSize(),
                'hash' => $hash,
            ]);

            return $model;
        });
    }

    /**
     * Определить тип 3D модели
     * @throws Exception
     */
    private function detectModelType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'glb' => 'glb',
            'gltf' => 'gltf',
            'obj' => 'obj',
            'fbx' => 'fbx',
            default => throw new Exception('Неподдерживаемый формат модели'),
        };
    }

    /**
     * Получить подписанный URL для скачивания модели
     * SECURITY: Временный URL с подписью, защита от IDOR
     */
    public function getSignedDownloadUrl(Model3D $model, int $expirationMinutes = 60): string
    {
        if (!$this->storage->disk('private')->exists($model->file_path)) {
            throw new Exception('Файл модели не найден', 404);
        }

        return $this->storage->disk('private')->temporaryUrl(
            $model->file_path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Инкремент счётчика скачиваний для аналитики
     */
    public function recordDownload(Model3D $model): void
    {
        $model->increment('download_count');
    }

    /**
     * Инкремент счётчика просмотров
     */
    public function recordView(Model3D $model): void
    {
        $model->increment('view_count');
    }
}
