<?php declare(strict_types=1);

namespace Modules\RealEstate\Listeners;

use Modules\RealEstate\Events\DealCompleted;
use Modules\RealEstate\Models\Property;
use Illuminate\Support\Facades\Log;

final class UpdatePropertyStatusOnDealComplete
{
    public function handle(DealCompleted $event): void
    {
        $property = $event->property;

        try {
            $property->markAsSold();

            Log::channel('audit')->info('real_estate.property.status.updated.on_deal_complete', [
                'property_id' => $property->id,
                'booking_id' => $event->booking->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.property.status.update.failed', [
                'property_id' => $property->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
