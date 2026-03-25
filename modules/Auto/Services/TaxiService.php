<?php

declare(strict_types=1);

namespace Modules\Auto\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Auto\Models\TaxiRide;

/**
 * TaxiService — управление поездками такси.
 * CANON 2026: $this->db->transaction, audit-лог, correlation_id.
 */
final class TaxiService
{
    public function createRide(array $data): TaxiRide
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($data, $correlationId) {
            $this->log->channel('audit')->info('Creating taxi ride', [
                'correlation_id' => $correlationId,
                'driver_id'      => $data['driver_id'] ?? null,
                'passenger_id'   => $data['passenger_id'] ?? null,
            ]);

            return TaxiRide::create([
                'driver_id'       => $data['driver_id'],
                'passenger_id'    => $data['passenger_id'],
                'vehicle_class'   => $data['vehicle_class'] ?? 'economy',
                'pickup_lat'      => $data['pickup_lat'] ?? 0,
                'pickup_lng'      => $data['pickup_lng'] ?? 0,
                'dropoff_lat'     => $data['dropoff_lat'] ?? 0,
                'dropoff_lng'     => $data['dropoff_lng'] ?? 0,
                'distance_km'     => $data['distance_km'] ?? 0,
                'fare_amount'     => $data['fare_amount'] ?? 0,
                'status'          => 'pending',
                'correlation_id'  => $correlationId,
            ]);
        });
    }

    public function completeRide(TaxiRide $ride): TaxiRide
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($ride, $correlationId) {
            $this->log->channel('audit')->info('Completing taxi ride', [
                'correlation_id' => $correlationId,
                'ride_id'        => $ride->id,
            ]);

            $ride->update([
                'status'         => 'completed',
                'completed_at'   => now(),
                'correlation_id' => $correlationId,
            ]);

            return $ride->fresh();
        });
    }
}
