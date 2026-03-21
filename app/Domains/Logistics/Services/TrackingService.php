<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Logistics\Models\Shipment;
use App\Domains\Logistics\Models\ShipmentTracking;
use Illuminate\Support\Facades\DB;

final class TrackingService
{
    public function addTrackingEvent(
        Shipment $shipment,
        string $eventType,
        ?string $location,
        ?string $notes,
        string $correlationId,
    ): ShipmentTracking {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'addTrackingEvent'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL addTrackingEvent', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getShipmentHistory'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getShipmentHistory', ['domain' => __CLASS__]);

        return $shipment->tracking()->orderBy('event_time', 'desc')->get();
    }
}
