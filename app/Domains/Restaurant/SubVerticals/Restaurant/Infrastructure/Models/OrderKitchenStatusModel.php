<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;

final class OrderKitchenStatusModel extends Model
{
    protected $table = 'order_kitchen_statuses';

    protected $fillable = [
        'order_id',
        'kitchen_station_id',
        'status',
        'priority',
        'estimated_preparation_minutes',
        'started_at',
        'completed_at',
        'problem_comment',
        'notes',
        'is_from_marketplace',
        'is_vip',
    ];

    protected $casts = [
        'status' => OrderKitchenStatusEnum::class,
        'priority' => OrderPriority::class,
        'estimated_preparation_minutes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_from_marketplace' => 'boolean',
        'is_vip' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant() !== null) {
                $query->whereHas('kitchenStation', function ($q) {
                    $q->where('kitchen_stations.tenant_id', tenant()->id);
                });
            }
        });
    }

    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(KitchenStationModel::class, 'kitchen_station_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    public function toDomain(): OrderKitchenStatus
    {
        return new OrderKitchenStatus(
            id: $this->id,
            orderId: $this->order_id,
            kitchenStationId: $this->kitchen_station_id,
            status: $this->status,
            priority: $this->priority,
            estimatedPreparationTime: new PreparationTime($this->estimated_preparation_minutes),
            startedAt: $this->started_at?->toImmutable(),
            completedAt: $this->completed_at?->toImmutable(),
            problemComment: $this->problem_comment,
            notes: $this->notes,
            isFromMarketplace: $this->is_from_marketplace,
            isVip: $this->is_vip,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }

    public static function fromDomain(OrderKitchenStatus $status): self
    {
        return new self([
            'id' => $status->id > 0 ? $status->id : null,
            'order_id' => $status->orderId,
            'kitchen_station_id' => $status->kitchenStationId,
            'status' => $status->status,
            'priority' => $status->priority,
            'estimated_preparation_minutes' => $status->estimatedPreparationTime->minutes,
            'started_at' => $status->startedAt,
            'completed_at' => $status->completedAt,
            'problem_comment' => $status->problemComment,
            'notes' => $status->notes,
            'is_from_marketplace' => $status->isFromMarketplace,
            'is_vip' => $status->isVip,
        ]);
    }
}
