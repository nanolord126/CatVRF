<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Entertainment\Events\BookingCreated;
use App\Domains\Entertainment\Models\Booking;
use App\Domains\Entertainment\Models\EntertainmentVenue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BookingService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly \App\Domains\Entertainment\Services\TicketingService $ticketingService,
    ) {}

    public function createBooking(int $venueId, int $scheduleId, int $customerId, int $numberOfSeats, string $correlationId): Booking
    {


        try {
            return $this->db->transaction(function () use ($venueId, $scheduleId, $customerId, $numberOfSeats, $correlationId) {
                $venue = EntertainmentVenue::findOrFail($venueId);
                $schedule = \App\Domains\Entertainment\Models\EventSchedule::findOrFail($scheduleId);

                $totalPrice = $schedule->ticket_price * $numberOfSeats;
                $commissionAmount = $totalPrice * 0.14;

                $booking = Booking::create([
                    'tenant_id' => tenant('id'),
                    'venue_id' => $venueId,
                    'event_schedule_id' => $scheduleId,
                    'customer_id' => $customerId,
                    'number_of_seats' => $numberOfSeats,
                    'total_price' => $totalPrice,
                    'commission_amount' => $commissionAmount,
                    'booking_date' => now(),
                    'status' => 'pending',
                    'transaction_id' => Str::uuid(),
                    'correlation_id' => $correlationId,
                ]);

                $schedule->decrement('available_seats', $numberOfSeats);

                event(new BookingCreated($booking, $correlationId));

                $this->log->channel('audit')->info('Booking created', [
                    'booking_id' => $booking->id,
                    'venue_id' => $venueId,
                    'customer_id' => $customerId,
                    'seats' => $numberOfSeats,
                    'total_price' => $totalPrice,
                    'commission' => $commissionAmount,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to create booking', [
                'venue_id' => $venueId,
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function cancelBooking(Booking $booking, string $reason, string $correlationId): void
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $this->db->transaction(function () use ($booking, $reason, $correlationId) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $schedule = $booking->eventSchedule;
                $schedule->increment('available_seats', $booking->number_of_seats);

                $this->log->channel('audit')->info('Booking cancelled', [
                    'booking_id' => $booking->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to cancel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
