<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\DeliverySlotRepositoryInterface;
use Modules\Flowers\Domain\Entities\DeliverySlot;
use Modules\Flowers\Infrastructure\Models\DeliverySlotModel;
use Carbon\CarbonImmutable;

final class EloquentDeliverySlotRepository implements DeliverySlotRepositoryInterface
{
    public function findById(int $id): ?DeliverySlot
    {
        $model = DeliverySlotModel::find($id);
        return $model?->toDomain();
    }

    public function save(DeliverySlot $slot): DeliverySlot
    {
        $model = DeliverySlotModel::fromDomain($slot);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        DeliverySlotModel::destroy($id);
    }

    public function getByVenue(int $venueId, CarbonImmutable $date): array
    {
        return DeliverySlotModel::where('venue_id', $venueId)
            ->byDate($date)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getAvailableSlots(int $venueId, CarbonImmutable $date): array
    {
        return DeliverySlotModel::where('venue_id', $venueId)
            ->byDate($date)
            ->available()
            ->orderBy('start_time')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function bookSlot(int $slotId, int $quantity = 1): DeliverySlot
    {
        $model = DeliverySlotModel::findOrFail($slotId);
        $entity = $model->toDomain();
        $updatedEntity = $entity->book($quantity);
        
        $model = DeliverySlotModel::fromDomain($updatedEntity);
        $model->save();
        
        return $model->toDomain();
    }

    public function releaseBooking(int $slotId, int $quantity = 1): DeliverySlot
    {
        $model = DeliverySlotModel::findOrFail($slotId);
        $entity = $model->toDomain();
        $updatedEntity = $entity->releaseBooking($quantity);
        
        $model = DeliverySlotModel::fromDomain($updatedEntity);
        $model->save();
        
        return $model->toDomain();
    }
}
