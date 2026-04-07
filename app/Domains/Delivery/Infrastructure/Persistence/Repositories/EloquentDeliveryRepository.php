<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Infrastructure\Persistence\Repositories;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Delivery\Domain\DTOs\DeliveryData;
use App\Domains\Delivery\Domain\Entities\Delivery;
use App\Domains\Delivery\Domain\Repositories\DeliveryRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final class EloquentDeliveryRepository implements DeliveryRepositoryInterface
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {}

    public function create(DeliveryData $data): Delivery
    {
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
        return $this->db->transaction(function () use ($data) {
            return Delivery::create([
                'order_id' => $data->order_id,
                'tenant_id' => $data->tenant_id,
                'courier_id' => $data->courier_id,
                'status' => $data->status,
                'from_address' => $data->from_address,
                'to_address' => $data->to_address,
                'payload' => $data->payload,
                'correlation_id' => $data->correlation_id ?? Str::uuid()->toString(),
                'uuid' => Str::uuid()->toString(),
            ]);
        });
    }

    public function findById(string $id): ?Delivery
    {
        return Delivery::find($id);
    }

    public function update(string $id, array $data): bool
    {
        return (bool) $this->db->transaction(function () use ($id, $data) {
            return Delivery::where('id', $id)->update($data);
        });
    }

    public function delete(string $id): bool
    {
        return (bool) $this->db->transaction(function () use ($id) {
            return Delivery::destroy($id);
        });
    }

    public function getByStatus(string $status): Collection
    {
        return Delivery::where('status', $status)->get();
    }

    public function assignCourier(string $deliveryId, int $courierId): bool
    {
        return $this->update($deliveryId, ['courier_id' => $courierId]);
    }
}
