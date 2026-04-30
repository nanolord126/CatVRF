<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Modules\Restaurant\Domain\Repositories\OrderKitchenStatusRepositoryInterface;
use Modules\Restaurant\Infrastructure\Models\OrderKitchenStatusModel;
use Carbon\CarbonImmutable;

final readonly class EloquentOrderKitchenStatusRepository implements OrderKitchenStatusRepositoryInterface
{
    public function findById(int $id): ?OrderKitchenStatus
    {
        $model = OrderKitchenStatusModel::find($id);
        return $model?->toDomain();
    }

    public function findByOrderId(int $orderId): Collection
    {
        return OrderKitchenStatusModel::where('order_id', $orderId)
            ->with('kitchenStation')
            ->get()
            ->map(fn (OrderKitchenStatusModel $model) => $model->toDomain());
    }

    public function findByStationId(int $stationId): Collection
    {
        return OrderKitchenStatusModel::where('kitchen_station_id', $stationId)
            ->whereNotIn('status', [OrderKitchenStatusEnum::SERVED, OrderKitchenStatusEnum::CANCELLED])
            ->orderByRaw("FIELD(priority, 'emergency', 'vip', 'urgent', 'high', 'normal')")
            ->orderBy('created_at')
            ->get()
            ->map(fn (OrderKitchenStatusModel $model) => $model->toDomain());
    }

    public function findByStatus(OrderKitchenStatusEnum $status): Collection
    {
        return OrderKitchenStatusModel::where('status', $status)
            ->with('kitchenStation')
            ->orderBy('created_at')
            ->get()
            ->map(fn (OrderKitchenStatusModel $model) => $model->toDomain());
    }

    public function findActiveOrdersForStation(int $stationId): Collection
    {
        return OrderKitchenStatusModel::where('kitchen_station_id', $stationId)
            ->whereIn('status', [
                OrderKitchenStatusEnum::PENDING,
                OrderKitchenStatusEnum::IN_PROGRESS,
                OrderKitchenStatusEnum::READY,
                OrderKitchenStatusEnum::PROBLEM,
            ])
            ->orderByRaw("FIELD(priority, 'emergency', 'vip', 'urgent', 'high', 'normal')")
            ->orderBy('created_at')
            ->with('kitchenStation', 'order')
            ->get()
            ->map(fn (OrderKitchenStatusModel $model) => $model->toDomain());
    }

    public function save(OrderKitchenStatus $status): void
    {
        $model = OrderKitchenStatusModel::fromDomain($status);
        $model->save();
    }

    public function delete(int $id): void
    {
        OrderKitchenStatusModel::findOrFail($id)->delete();
    }

    public function getOverdueOrders(): Collection
    {
        return OrderKitchenStatusModel::whereIn('status', [
            OrderKitchenStatusEnum::PENDING,
            OrderKitchenStatusEnum::IN_PROGRESS,
        ])
            ->whereNotNull('started_at')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, started_at, NOW()) > estimated_preparation_minutes')
            ->with('kitchenStation', 'order')
            ->get()
            ->map(fn (OrderKitchenStatusModel $model) => $model->toDomain());
    }

    public function getOrdersByPriority(int $tenantId): Collection
    {
        return OrderKitchenStatusModel::whereHas('kitchenStation', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
            ->whereIn('status', [
                OrderKitchenStatusEnum::PENDING,
                OrderKitchenStatusEnum::IN_PROGRESS,
            ])
            ->orderByRaw("FIELD(priority, 'emergency', 'vip', 'urgent', 'high', 'normal')")
            ->orderBy('created_at')
            ->with('kitchenStation', 'order')
            ->get()
            ->map(fn (OrderKitchenStatusModel $model) => $model->toDomain());
    }
}
