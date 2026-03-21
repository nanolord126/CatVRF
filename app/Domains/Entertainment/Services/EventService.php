<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Entertainment\Events\EventCancelled;
use App\Domains\Entertainment\Models\EntertainmentEvent;
use App\Domains\Entertainment\Models\EntertainmentVenue;
use Illuminate\Support\Facades\DB;

final class EventService
{
    public function createEvent(int $venueId, int $entertainerId, string $name, string $description, string $eventType, \DateTime $startDate, \DateTime $endDate, int $totalSeats, float $basePrice, ?float $vipPrice, string $correlationId): EntertainmentEvent
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createEvent'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createEvent', ['domain' => __CLASS__]);

        try {
            return DB::transaction(function () use ($venueId, $entertainerId, $name, $description, $eventType, $startDate, $endDate, $totalSeats, $basePrice, $vipPrice, $correlationId) {
                $venue = EntertainmentVenue::findOrFail($venueId);

                $event = EntertainmentEvent::create([
                    'tenant_id' => tenant('id'),
                    'venue_id' => $venueId,
                    'entertainer_id' => $entertainerId,
                    'name' => $name,
                    'description' => $description,
                    'event_type' => $eventType,
                    'event_date_start' => $startDate,
                    'event_date_end' => $endDate,
                    'total_seats' => $totalSeats,
                    'available_seats' => $totalSeats,
                    'base_price' => $basePrice,
                    'vip_price' => $vipPrice,
                    'status' => 'scheduled',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Entertainment event created', [
                    'event_id' => $event->id,
                    'venue_id' => $venueId,
                    'name' => $name,
                    'event_type' => $eventType,
                    'correlation_id' => $correlationId,
                ]);

                return $event;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create event', [
                'venue_id' => $venueId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function cancelEvent(EntertainmentEvent $event, string $correlationId): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'cancelEvent'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelEvent', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($event, $correlationId) {
                $event->update([
                    'status' => 'cancelled',
                    'correlation_id' => $correlationId,
                ]);

                event(new EventCancelled($event, $correlationId));

                Log::channel('audit')->info('Entertainment event cancelled', [
                    'event_id' => $event->id,
                    'venue_id' => $event->venue_id,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to cancel event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
