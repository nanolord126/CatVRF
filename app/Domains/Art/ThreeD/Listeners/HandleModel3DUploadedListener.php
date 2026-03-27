<?php

declare(strict_types=1);

namespace App\Domains\Art\ThreeD\Listeners;

use App\Domains\Art\ThreeD\Events\Model3DUploaded;
use App\Domains\Art\ThreeD\Jobs\Process3DModelJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события загрузки 3D модели
 * Запускает асинхронную обработку (оптимизация, превью, метаданные)
 * 
 * SECURITY:
 * - Dispatch job в очередь с correlation_id
 * - Логирование всех действий
 * - Обработка ошибок с информативными сообщениями
 */
final class HandleModel3DUploadedListener implements ShouldQueue
{
    /**
     * Обработать событие
     */
    public function handle(Model3DUploaded $event): void
    {
        try {
            Log::channel('audit')->info('Начало обработки загруженной 3D модели', [
                'correlation_id' => $event->correlationId,
                'model_id' => $event->model->id,
                'model_uuid' => $event->model->uuid,
                'tenant_id' => $event->tenantId,
            ]);

            // Запускаем асинхронный job для обработки
            Queue::dispatch(
                new Process3DModelJob(
                    model: $event->model,
                    correlationId: $event->correlationId,
                )
            );

            Log::channel('audit')->info('Job обработки 3D модели добавлен в очередь', [
                'correlation_id' => $event->correlationId,
                'model_id' => $event->model->id,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Ошибка при запуске обработки 3D модели', [
                'correlation_id' => $event->correlationId,
                'model_id' => $event->model->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Обновляем статус модели
            $event->model->update([
                'status' => 'rejected',
                'rejection_reason' => 'Ошибка при запуске обработки: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
