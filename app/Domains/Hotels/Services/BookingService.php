<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Models\RoomInventory;
use App\Domains\Hotels\Models\RoomType;
use Illuminate\Support\Facades\DB;
use Throwable;

final class BookingService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createBooking(
        int $hotelId,
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        int $numberOfGuests,
        int $guestId,
        ?string $specialRequests = null,
        string $correlationId = '',
    ): Booking {


        try {
            $this->log->channel('audit')->info('Creating booking', [
                'hotel_id' => $hotelId,
                'room_type_id' => $roomTypeId,
                'check_in_date' => $checkInDate,
                'correlation_id' => $correlationId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );

            $booking = $this->db->transaction(function () use (
                $hotelId,
                $roomTypeId,
                $checkInDate,
                $checkOutDate,
                $numberOfGuests,
                $guestId,
                $specialRequests,
                $correlationId,
            ) {
                $roomType = RoomType::findOrFail($roomTypeId);
                
                // Calculate nights
                $checkInDt = \Carbon\Carbon::parse($checkInDate);
                $checkOutDt = \Carbon\Carbon::parse($checkOutDate);
                $nights = $checkInDt->diffInDays($checkOutDt);
                
                if ($nights <= 0) {
                    throw new \Exception('Invalid check-in/out dates');
                }

                // Calculate prices
                $subtotal = $roomType->base_price_per_night * $nights;
                $commission = (int) ($subtotal * 14 / 100);
                $cleaningFee = 50000; // 500 рублей
                $total = $subtotal + $commission + $cleaningFee;

                return Booking::create([
                    'tenant_id' => tenant('id'),
                    'hotel_id' => $hotelId,
                    'room_type_id' => $roomTypeId,
                    'guest_id' => $guestId,
                    'confirmation_code' => strtoupper(\Illuminate\Support\Str::random(8)),
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'number_of_guests' => $numberOfGuests,
                    'nights_count' => $nights,
                    'subtotal_price' => $subtotal,
                    'cleaning_fee' => $cleaningFee,
                    'commission_price' => $commission,
                    'total_price' => $total,
                    'booking_status' => 'confirmed',
                    'payment_status' => 'pending',
                    'special_requests' => $specialRequests,
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Booking created successfully', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Booking creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function confirmBooking(Booking $booking, string $correlationId = ''): Booking
    {


        try {
            $this->log->channel('audit')->info('Confirming booking', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            $booking->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->log->channel('audit')->info('Booking confirmed', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Booking confirmation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function cancelBooking(Booking $booking, string $reason = '', string $correlationId = ''): bool
    {


        try {
            $this->log->channel('audit')->info('Cancelling booking', [
                'booking_id' => $booking->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $booking->update([
                'booking_status' => 'cancelled',
            ]);

            $this->log->channel('audit')->info('Booking cancelled', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
