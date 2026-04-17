<?php declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiRideCompletedEvent;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessTaxiPaymentJob;
use App\Jobs\ProcessDriverPayoutJob;

/**
 * Listener for TaxiRideCompletedEvent.
 * Triggers payment processing and driver payout jobs.
 */
final readonly class ProcessPaymentAndPayoutListener
{
    public function handle(TaxiRideCompletedEvent $event): void
    {
        Log::channel('audit')->info('Payment and payout processing triggered', [
            'ride_id' => $event->ride->id,
            'final_price_rubles' => $event->ride->final_price_kopeki / 100,
            'correlation_id' => $event->correlationId,
        ]);

        ProcessTaxiPaymentJob::dispatch($event->ride->id, $event->correlationId)
            ->onQueue('payments');

        ProcessDriverPayoutJob::dispatch($event->ride->driver_id, $event->ride->final_price_kopeki, $event->correlationId)
            ->onQueue('payouts');
    }
}
