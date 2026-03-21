<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Models\Shipment;
use App\Domains\Logistics\Models\ShipmentTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TrackingService
{
    public function addTrackingEvent(
        Shipment $shipment,
        string $eventType,
        ?string $location,
        ?string $notes,
        string $correlationId,
    ): ShipmentTracking {
        return DB::transaction(function () use (
            $shipment,
            $eventType,
            $location,
            $notes,
            $correlationId,
        ) {
            $tracking = ShipmentTracking::create([
                'tenant_id' => $shipment->tenant_id,
                'shipment_id' => $shipment->id,
                'event_type' => $eventType,
                'location' => $location,
                'notes' => $notes,
                'event_time' => now(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Tracking event added', [
                'shipment_id' => $shipment->id,
                'event_type' => $eventType,
                'correlation_id' => $correlationId,
            ]);

            return $tracking;
        });
    }

    public function getShipmentHistory(Shipment $shipment): \Illuminate\Database\Eloquent\Collection
    {
        return $shipment->tracking()->orderBy('event_time', 'desc')->get();
    }
}
