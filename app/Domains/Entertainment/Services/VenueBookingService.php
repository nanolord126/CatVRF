<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'bookVenue'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL bookVenue', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'confirmBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL confirmBooking', ['domain' => __CLASS__]);

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
