<?php declare(strict_types=1);

namespace Modules\RealEstate\Observers;

use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class PropertyObserver
{
    public function created(Property $property): void
    {
        Log::channel('audit')->info('real_estate.property.observed.created', [
            'property_id' => $property->id,
            'owner_id' => $property->owner_id,
            'correlation_id' => $property->correlation_id,
        ]);

        Cache::forget("property_statistics:{$property->tenant_id}");
    }

    public function updated(Property $property): void
    {
        $oldStatus = $property->getOriginal('status');
        $newStatus = $property->status->value;

        if ($oldStatus !== $newStatus) {
            Log::channel('audit')->info('real_estate.property.observed.status_changed', [
                'property_id' => $property->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'correlation_id' => $property->correlation_id,
            ]);
        }

        Cache::forget("property_availability:{$property->id}");
        Cache::forget("property_statistics:{$property->tenant_id}");
    }

    public function deleted(Property $property): void
    {
        Log::channel('audit')->info('real_estate.property.observed.deleted', [
            'property_id' => $property->id,
            'correlation_id' => $property->correlation_id,
        ]);

        Cache::forget("property_availability:{$property->id}");
        Cache::forget("property_statistics:{$property->tenant_id}");
    }

    public function restored(Property $property): void
    {
        Log::channel('audit')->info('real_estate.property.observed.restored', [
            'property_id' => $property->id,
            'correlation_id' => $property->correlation_id,
        ]);
    }

    public function forceDeleted(Property $property): void
    {
        Log::channel('audit')->info('real_estate.property.observed.force_deleted', [
            'property_id' => $property->id,
            'correlation_id' => $property->correlation_id,
        ]);
    }
}
