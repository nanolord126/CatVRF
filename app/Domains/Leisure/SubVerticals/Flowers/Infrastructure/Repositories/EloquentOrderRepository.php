<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\OrderRepositoryInterface;
use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Modules\Flowers\Infrastructure\Models\OrderModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function findById(int $id): ?Order
    {
        $model = OrderModel::find($id);
        return $model?->toDomain();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $model = OrderModel::where('order_number', $orderNumber)->first();
        return $model?->toDomain();
    }

    public function save(Order $order): Order
    {
        $model = OrderModel::fromDomain($order);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        OrderModel::destroy($id);
    }

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator
    {
        $query = OrderModel::where('venue_id', $venueId);

        $this->applyFilters($query, $filters);

        return $query->with(['client', 'florist', 'items', 'modifiers'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getByClient(int $clientId, array $filters = []): LengthAwarePaginator
    {
        $query = OrderModel::where('client_id', $clientId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getByFlorist(int $floristId, array $filters = []): LengthAwarePaginator
    {
        $query = OrderModel::where('florist_id', $floristId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getByStatus(OrderStatus $status, ?int $venueId = null): array
    {
        $query = OrderModel::where('status', $status);

        if ($venueId) {
            $query->where('venue_id', $venueId);
        }

        return $query->with(['client', 'florist', 'items'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getPendingOrders(int $venueId): array
    {
        return $this->getByStatus(OrderStatus::PENDING, $venueId);
    }

    public function getInAssemblyOrders(int $venueId): array
    {
        return OrderModel::where('venue_id', $venueId)
            ->whereIn('status', [OrderStatus::IN_ASSEMBLY, OrderStatus::ASSEMBLED])
            ->with(['client', 'florist', 'items'])
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getReadyForDeliveryOrders(int $venueId): array
    {
        return OrderModel::where('venue_id', $venueId)
            ->whereIn('status', [OrderStatus::READY_FOR_DELIVERY, OrderStatus::OUT_FOR_DELIVERY])
            ->with(['client', 'florist', 'items'])
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getOverdueOrders(int $venueId): array
    {
        return OrderModel::where('venue_id', $venueId)
            ->overdue()
            ->with(['client', 'florist', 'items'])
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getUrgentOrders(int $venueId): array
    {
        return OrderModel::where('venue_id', $venueId)
            ->urgent()
            ->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::PICKED_UP, OrderStatus::CANCELLED, OrderStatus::REFUNDED])
            ->with(['client', 'florist', 'items'])
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getCorporateOrders(int $venueId): array
    {
        return OrderModel::where('venue_id', $venueId)
            ->corporate()
            ->with(['client', 'florist', 'items'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['is_urgent'])) {
            $query->where('is_urgent', $filters['is_urgent']);
        }

        if (isset($filters['is_corporate'])) {
            $query->where('is_corporate', $filters['is_corporate']);
        }
    }
}
