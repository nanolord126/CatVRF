<?php

declare(strict_types=1);

namespace Modules\RealEstate\Observers;

use Modules\RealEstate\Models\Property;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Cache\Repository;

final class PropertyObserver
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Repository $cache,
    ) {}

    public function created(Property $property): void
    {
        $this->log->channel('audit')->info('real_estate.property.observed.created', [
            'property_id' => $property->id,
            'owner_id' => $property->owner_id,
            'correlation_id' => $property->correlation_id,
        ]);

        $this->cache->forget("property_statistics:{$property->tenant_id}");
    }

    public function updated(Property $property): void
    {
        $oldStatus = $property->getOriginal('status');
        $newStatus = $property->status->value;

        if ($oldStatus !== $newStatus) {
            $this->log->channel('audit')->info('real_estate.property.observed.status_changed', [
                'property_id' => $property->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'correlation_id' => $property->correlation_id,
            ]);
        }

        $this->cache->forget("property_availability:{$property->id}");
        $this->cache->forget("property_statistics:{$property->tenant_id}");
    }

    public function deleted(Property $property): void
    {
        $this->log->channel('audit')->info('real_estate.property.observed.deleted', [
            'property_id' => $property->id,
            'correlation_id' => $property->correlation_id,
        ]);

        $this->cache->forget("property_availability:{$property->id}");
        $this->cache->forget("property_statistics:{$property->tenant_id}");
    }

    public function restored(Property $property): void
    {
        $this->log->channel('audit')->info('real_estate.property.observed.restored', [
            'property_id' => $property->id,
            'correlation_id' => $property->correlation_id,
        ]);
    }

    public function forceDeleted(Property $property): void
    {
        $this->log->channel('audit')->info('real_estate.property.observed.force_deleted', [
            'property_id' => $property->id,
            'correlation_id' => $property->correlation_id,
        ]);
    }
}
