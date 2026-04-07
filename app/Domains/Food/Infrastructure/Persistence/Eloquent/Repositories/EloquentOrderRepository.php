<?php

declare(strict_types=1);

namespace App\Domains\Food\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Food\Domain\Entities\Order;
use App\Domains\Food\Domain\Repositories\OrderRepositoryInterface;
use App\Domains\Food\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly OrderModel $model)
    {

    }

    public function findById(Uuid $id): ?Order
    {
        $model = $this->model->newQuery()->with('items')->find($id->toString());

        if (!$model) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        return $this->toEntity($model);
    }

    public function findByClientId(Uuid $clientId): Collection
    {
        $models = $this->model->newQuery()
            ->with('items')
            ->where('client_id', $clientId->toString())
            ->get();

        return $models->map(fn (OrderModel $model) => $this->toEntity($model));
    }

    public function save(Order $order): void
    {
        $model = $this->model->newQuery()->find($order->id->toString()) ?? new OrderModel();

        $model->id = $order->id->toString();
        $model->tenant_id = $order->tenantId->toString();
        $model->restaurant_id = $order->restaurantId->toString();
        $model->client_id = $order->clientId->toString();
        $model->total_price = $order->totalPrice->getAmount();
        $model->currency = $order->totalPrice->getCurrency();
        $model->status = $order->status->value;
        $model->correlation_id = $order->correlationId?->toString();

        $model->save();

        $itemIds = $order->items->pluck('id')->map(fn (Uuid $id) => $id->toString());

        $model->items()->whereNotIn('id', $itemIds)->delete();

        foreach ($order->items as $item) {
            $model->items()->updateOrCreate(
                ['id' => $item->id->toString()],
                [
                    'dish_id' => $item->dishId->toString(),
                    'dish_name' => $item->dishName,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unitPrice->getAmount(),
                    'modifiers' => $item->modifiers->map(fn ($mod) => $mod->toArray())->all(),
                    'total_price' => $item->getTotalPrice()->getAmount(),
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $criteria
     * @return Collection<Order>
     */
    public function search(array $criteria): Collection
    {
        $query = $this->model->newQuery()->with('items');

        $this->applyCriteria($query, $criteria);

        return $query->get()->map(fn (OrderModel $model) => $this->toEntity($model));
    }

    private function toEntity(OrderModel $model): Order
    {
        return Order::fromArray($model->toArray());
    }

    /**
     * @param Builder $query
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(Builder $query, array $criteria): void
    {
        if (isset($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        if (isset($criteria['restaurant_id'])) {
            $query->where('restaurant_id', $criteria['restaurant_id']);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }
    }
}
