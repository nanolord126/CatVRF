<?php

declare(strict_types=1);

namespace App\Domains\ThreeD\Jobs;

use App\Domains\ThreeD\Models\Model3D;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class Process3DModelJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600; // 10 минут
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Model3D $model,
        private readonly string $correlationId,
    ) {
    }

    /**
     * Обработка загруженной 3D модели
     * Включает: оптимизацию, генерацию превью, экстракцию метаданных
     */
    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Начало обработки 3D модели', [
                'model_id' => $this->model->id,
                'correlation_id' => $this->correlationId,
                'file_size' => $this->model->file_size,
            ]);

            // 1. Проверка файла
            if (!Storage::disk('private')->exists($this->model->file_path)) {
                throw new \Exception('Файл модели не найден');
            }

            // 2. Оптимизация модели (если GLB → сжатие, удаление лишних текстур)
            $this->optimizeModel();

            // 3. Генерация превью-изображения (снимок с камеры)
            $this->generatePreview();

            // 4. Экстракция метаданных
            $this->extractMetadata();

            // Обновляем статус на активный
            $this->model->update([
                'status' => 'active',
            ]);

            Log::channel('audit')->info('Обработка 3D модели завершена', [
                'model_id' => $this->model->id,
                'correlation_id' => $this->correlationId,
                'status' => 'active',
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Ошибка при обработке 3D модели', [
                'model_id' => $this->model->id,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Обновляем статус на отклонено
            $this->model->update([
                'status' => 'rejected',
                'rejection_reason' => $e->getMessage(),
            ]);

            // Пробросим исключение для retry механизма
            throw $e;
        }
    }

    /**
     * Оптимизация модели (削減 размера, сжатие текстур)
     * TODO: Использовать gltf-transform или similar tool
     */
    private function optimizeModel(): void
    {
        // В production использовать gltf-transform CLI
        // Пример: gltf-transform optimize input.glb output.glb
        Log::channel('audit')->info('Оптимизация модели (стаб)', [
            'model_id' => $this->model->id,
        ]);
    }

    /**
     * Генерация PNG превью модели для быстрого отображения
     * TODO: Использовать Three.js headless renderer или similar
     */
    private function generatePreview(): void
    {
        // В production использовать headless Three.js rendering
        // или Babylon.js Node.js API для генерации PNG
        Log::channel('audit')->info('Генерация превью (стаб)', [
            'model_id' => $this->model->id,
        ]);
    }

    /**
     * Экстракция метаданных из модели (размер, полигоны, текстуры)
     */
    private function extractMetadata(): void
    {
        try {
            // SECURITY: Получаем только путь в private storage
            $filePath = $this->model->file_path;

            // Читаем информацию о файле
            $fileSize = Storage::disk('private')->size($filePath);

            // Базовые метаданные
            $metadata = [
                'processed_size' => $fileSize,
                'format' => $this->model->model_type,
                'processing_date' => now()->toIso8601String(),
                // В production добавить: polygon_count, texture_count и т.д.
            ];

            $this->model->update([
                'metadata' => $metadata,
            ]);

            Log::channel('audit')->info('Метаданные модели экстрагированы', [
                'model_id' => $this->model->id,
                'metadata' => $metadata,
            ]);

        } catch (\Exception $e) {
            Log::warning('Ошибка при экстракции метаданных', [
                'model_id' => $this->model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
