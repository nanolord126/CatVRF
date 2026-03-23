<?php

declare(strict_types=1);

namespace App\Domains\ThreeD\Events;

use App\Domains\ThreeD\Models\Model3D;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * События загрузки 3D модели
 * SECURITY:
 * - Передача correlation_id для трейсинга
 * - Логирование в audit канал
 * - Dispatch асинхронного job для обработки
 */
final class Model3DUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Model3D $model,
        public readonly string $correlationId,
        public readonly int $tenantId,
    ) {
    }

    /**
     * Получить имена каналов трансляции
     */
    public function broadcastOn(): array
    {
        return ["tenant.{$this->tenantId}"];
    }

    /**
     * Получить имя события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'model3d.uploaded';
    }

    /**
     * Получить данные для трансляции
     */
    public function broadcastWith(): array
    {
        return [
            'model_id' => $this->model->id,
            'model_uuid' => $this->model->uuid,
            'model_name' => $this->model->name,
            'status' => $this->model->status,
            'correlation_id' => $this->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
