<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class VenueBookingService
{
    public function __construct()
    {
    }

    /**
     * Забронировать площадку на событие
     */
    public function bookVenue(
        int $venueId,
        string $eventName,
        string $eventDate,
        int $guestCount,
        string $correlationId,
    ): int {
        try {
            $bookingId = DB::transaction(function () use ($venueId, $eventName, $eventDate, $guestCount, $correlationId) {
                $bookingId = DB::table('venue_bookings')->insertGetId([
                    'venue_id' => $venueId,
                    'event_name' => $eventName,
                    'event_date' => $eventDate,
                    'guest_count' => $guestCount,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Venue booked', [
                    'booking_id' => $bookingId,
                    'venue_id' => $venueId,
                    'guests' => $guestCount,
                    'correlation_id' => $correlationId,
                ]);

                return $bookingId;
            });

            return $bookingId;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Venue booking failed', [
                'venue_id' => $venueId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Подтвердить бронирование (после оплаты)
     */
    public function confirmBooking(int $bookingId, string $correlationId): bool
    {
        try {
            DB::transaction(function () use ($bookingId, $correlationId) {
                DB::table('venue_bookings')
                    ->where('id', $bookingId)
                    ->update(['status' => 'confirmed']);

                Log::channel('audit')->info('Venue booking confirmed', [
                    'booking_id' => $bookingId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Venue booking confirmation failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
