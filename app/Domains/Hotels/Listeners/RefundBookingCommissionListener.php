<?php declare(strict_types=1);

namespace App\Domains\Hotels\Listeners;

use App\Domains\Hotels\Events\BookingCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RefundBookingCommissionListener implements ShouldQueue
{
    public function handle(BookingCancelled $event): void
    {
        try {
            Log::channel('audit')->info('Refunding booking commission', [
                'booking_id' => $event->booking->id,
                'correlation_id' => $event->correlationId,
                'reason' => $event->reason,
            ]);

            DB::transaction(function () use ($event) {
                $hotel = $event->booking->hotel;
                
                // Refund 14% commission to hotel balance
                $wallet = auth()->user()?->wallet ?? $hotel->wallet;
                
                if ($wallet) {
                    $wallet->balance += $event->booking->commission_price;
                    $wallet->save();
                }

                Log::channel('audit')->info('Booking commission refunded', [
                    'booking_id' => $event->booking->id,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to refund booking commission', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
