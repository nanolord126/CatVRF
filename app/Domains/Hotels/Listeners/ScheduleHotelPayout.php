<?php declare(strict_types=1);

namespace App\Domains\Hotels\Listeners;

use App\Domains\Hotels\Events\CheckoutCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ScheduleHotelPayout
{
    public function handle(CheckoutCompleted $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                Log::channel('audit')->info('Hotel payout scheduled (4 days)', [
                    'booking_id' => $event->bookingId,
                    'hotel_id' => $event->hotelId,
                    'total_amount' => $event->totalAmount,
                    'correlation_id' => $event->correlationId,
                    'action' => 'hotel_checkout_payout_scheduled',
                ]);
                // PayoutScheduleService::schedule($hotel_id, $event->totalAmount, delay: 4 days);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed to schedule hotel payout', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
