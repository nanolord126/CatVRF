<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\Interfaces\OrderKitchenStatusRepositoryInterface;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;
use Modules\Restaurant\Infrastructure\Models\OrderKitchenStatusModel;
use Carbon\CarbonImmutable;

final class OrderKitchenStatusRepository implements OrderKitchenStatusRepositoryInterface
{
    public function findById(int $id): ?OrderKitchenStatus
    {
        $model = OrderKitchenStatusModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByOrderId(int $orderId): ?OrderKitchenStatus
    {
        $model = OrderKitchenStatusModel::where('order_id', $orderId)->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function getByStation(int $stationId, ?array $statuses = null): array
    {
        $query = OrderKitchenStatusModel::where('kitchen_station_id', $stationId);

        if ($statuses !== null) {
            $query->whereIn('status', $statuses);
        }

        $models = $query
            ->orderBy('created_at', 'asc')
            ->get();

        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getActiveOrders(int $stationId): array
    {
        return $this->getByStation(
            $stationId,
            [OrderKitchenStatusEnum::PENDING, OrderKitchenStatusEnum::IN_PROGRESS]
        );
    }

    public function getOverdueOrders(int $stationId): array
    {
        $models = OrderKitchenStatusModel::where('kitchen_station_id', $stationId)
            ->whereIn('status', [OrderKitchenStatusEnum::PENDING, OrderKitchenStatusEnum::IN_PROGRESS])
            ->whereNotNull('started_at')
            ->get();

        return $models
            ->filter(fn ($model) => $this->isOrderOverdue($model))
            ->map(fn ($model) => $this->modelToEntity($model))
            ->toArray();
    }

    public function save(OrderKitchenStatus $orderStatus): OrderKitchenStatus
    {
        $model = OrderKitchenStatusModel::find($orderStatus->id);

        if ($model === null) {
            $model = new OrderKitchenStatusModel();
        }

        $model->order_id = $orderStatus->orderId;
        $model->kitchen_station_id = $orderStatus->kitchenStationId;
        $model->status = $orderStatus->status->value;
        $model->priority = $orderStatus->priority->value;
        $model->estimated_minutes = $orderStatus->estimatedPreparationTime->minutes;
        $model->started_at = $orderStatus->startedAt;
        $model->completed_at = $orderStatus->completedAt;
        $model->problem_comment = $orderStatus->problemComment;
        $model->notes = $orderStatus->notes;
        $model->is_from_marketplace = $orderStatus->isFromMarketplace;
        $model->is_vip = $orderStatus->isVip;

        $model->save();

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return OrderKitchenStatusModel::destroy($id) > 0;
    }

    public function getStatistics(int $stationId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $models = OrderKitchenStatusModel::where('kitchen_station_id', $stationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $total = $models->count();
        $completed = $models->where('status', OrderKitchenStatusEnum::READY->value)->count();
        $cancelled = $models->where('status', OrderKitchenStatusEnum::CANCELLED->value)->count();
        $problems = $models->where('status', OrderKitchenStatusEnum::PROBLEM->value)->count();

        // Среднее время приготовления
        $completedOrders = $models->where('status', OrderKitchenStatusEnum::READY->value);
        $avgPrepTime = $completedOrders->count() > 0
            ? $completedOrders->avg(fn ($model) => $model->started_at && $model->completed_at
                ? $model->started_at->diffInMinutes($model->completed_at)
                : null)
            : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'problems' => $problems,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'avg_preparation_minutes' => round($avgPrepTime, 2),
        ];
    }

    private function modelToEntity(OrderKitchenStatusModel $model): OrderKitchenStatus
    {
        return new OrderKitchenStatus(
            id: $model->id,
            orderId: $model->order_id,
            kitchenStationId: $model->kitchen_station_id,
            status: OrderKitchenStatusEnum::from($model->status),
            priority: OrderPriority::from($model->priority),
            estimatedPreparationTime: new PreparationTime($model->estimated_minutes),
            startedAt: $model->started_at ? CarbonImmutable::parse($model->started_at) : null,
            completedAt: $model->completed_at ? CarbonImmutable::parse($model->completed_at) : null,
            problemComment: $model->problem_comment,
            notes: $model->notes,
            isFromMarketplace: $model->is_from_marketplace,
            isVip: $model->is_vip,
            createdAt: CarbonImmutable::parse($model->created_at),
            updatedAt: CarbonImmutable::parse($model->updated_at),
        );
    }

    private function isOrderOverdue(OrderKitchenStatusModel $model): bool
    {
        if ($model->started_at === null) {
            return false;
        }

        $elapsed = $model->started_at->diffInMinutes(now());
        return $elapsed > $model->estimated_minutes;
    }
}
