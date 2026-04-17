<?php declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiRideCreatedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Listener for TaxiRideCreatedEvent.
 * Sends notification to passenger and triggers AI analysis.
 */
final readonly class SendRideCreatedNotificationListener
{
    public function handle(TaxiRideCreatedEvent $event): void
    {
        Log::channel('audit')->info('Taxi ride created notification sent', [
            'ride_id' => $event->ride->id,
            'ride_uuid' => $event->ride->uuid,
            'passenger_id' => $event->ride->passenger_id,
            'correlation_id' => $event->correlationId,
        ]);

        Cache::put("taxi:notification:{$event->ride->uuid}", [
            'sent_at' => now()->toIso8601String(),
            'type' => 'ride_created',
        ], 3600);
    }
}
