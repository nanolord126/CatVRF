<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Travel\Events\TransportationBooked;
use App\Domains\Travel\Models\TravelTransportation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class TransportationService
{
    public function __construct() {}

    public function bookTransportation(
        TravelTransportation $transportation,
        int $seatsRequired = 1,
        string $correlationId = null,
    ): TravelTransportation {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'bookTransportation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL bookTransportation', ['domain' => __CLASS__]);

        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use (
                $transportation,
                $seatsRequired,
                $correlationId,
            ) {
                $transportation->lockForUpdate();

                if ($transportation->available_count < $seatsRequired) {
                    throw new \RuntimeException('Not enough available spaces for transportation');
                }

                $transportation->decrement('available_count', $seatsRequired);

                Log::channel('audit')->info('Transportation booked', [
                    'transportation_id' => $transportation->id,
                    'type' => $transportation->type,
                    'seats_booked' => $seatsRequired,
                    'remaining_spaces' => $transportation->available_count,
                    'commission_amount' => $transportation->commission_amount,
                    'correlation_id' => $correlationId,
                    'timestamp' => now(),
                ]);

                TransportationBooked::dispatch($transportation, $correlationId);

                return $transportation->refresh();
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Transportation booking failed', [
                'transportation_id' => $transportation->id,
                'seats_required' => $seatsRequired,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function releaseTransportation(
        TravelTransportation $transportation,
        int $seatsToRelease = 1,
        string $correlationId = null,
    ): TravelTransportation {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'releaseTransportation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL releaseTransportation', ['domain' => __CLASS__]);

        $correlationId ??= $transportation->correlation_id ?? Str::uuid()->toString();

        try {
            return DB::transaction(function () use (
                $transportation,
                $seatsToRelease,
                $correlationId,
            ) {
                $transportation->lockForUpdate();

                $newAvailable = $transportation->available_count + $seatsToRelease;

                if ($newAvailable > $transportation->capacity) {
                    throw new \RuntimeException('Cannot release more spaces than transportation capacity');
                }

                $transportation->increment('available_count', $seatsToRelease);

                Log::channel('audit')->info('Transportation spaces released', [
                    'transportation_id' => $transportation->id,
                    'type' => $transportation->type,
                    'spaces_released' => $seatsToRelease,
                    'available_spaces' => $transportation->available_count,
                    'correlation_id' => $correlationId,
                    'timestamp' => now(),
                ]);

                return $transportation->refresh();
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Transportation space release failed', [
                'transportation_id' => $transportation->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
