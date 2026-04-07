<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Beauty\Domain\Entities\Consumable;
use App\Domains\Beauty\Domain\Repositories\ConsumableRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyConsumable;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyService;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация ConsumableRepositoryInterface.
 */
final class EloquentConsumableRepository implements ConsumableRepositoryInterface
{
    public function findById(int $id): ?Consumable
    {
        $model = BeautyConsumable::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByServiceId(ServiceId $serviceId): Collection
    {
        $service = BeautyService::where('uuid', $serviceId->getValue())->first();

        if ($service === null) {
            return new Collection();
        }

        return BeautyConsumable::where('service_id', $service->id)
            ->get()
            ->map(fn (BeautyConsumable $m) => $this->toDomain($m));
    }

    public function findBelowThreshold(int $tenantId): Collection
    {
        return BeautyConsumable::where('tenant_id', $tenantId)
            ->whereRaw('current_stock <= min_stock_threshold')
            ->get()
            ->map(fn (BeautyConsumable $m) => $this->toDomain($m));
    }

    public function save(Consumable $consumable): void
    {
        $service = BeautyService::where('uuid', $consumable->serviceId->getValue())->firstOrFail();

        $model = BeautyConsumable::firstOrNew(['id' => $consumable->id]);
        $model->tenant_id           = $consumable->tenantId;
        $model->service_id          = $service->id;
        $model->name                = $consumable->name;
        $model->unit                = $consumable->unit;
        $model->current_stock       = $consumable->currentStock;
        $model->hold_stock          = $consumable->holdStock;
        $model->min_stock_threshold = $consumable->minStockThreshold;
        $model->quantity_per_service = $consumable->quantityPerService;
        $model->correlation_id      = $consumable->correlationId;
        $model->save();
    }

    public function delete(int $id): bool
    {
        return (bool) BeautyConsumable::destroy($id);
    }

    /**
     * Маппинг Eloquent → Domain.
     */
    private function toDomain(BeautyConsumable $model): Consumable
    {
        return new Consumable(
            id: $model->id,
            tenantId: $model->tenant_id,
            serviceId: ServiceId::fromString($model->service->uuid),
            name: $model->name,
            unit: $model->unit,
            currentStock: $model->current_stock,
            holdStock: $model->hold_stock,
            minStockThreshold: $model->min_stock_threshold,
            quantityPerService: $model->quantity_per_service,
            correlationId: (string) $model->correlation_id,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at),
        );
    }
}
