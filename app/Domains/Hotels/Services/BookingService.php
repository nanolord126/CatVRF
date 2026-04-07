<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Models\Room;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис бронирования (основной CRUD).
 * Layer 3: Services — CatVRF 2026
 *
 * Создание, подтверждение и отмена бронирований.
 * FraudCheck + DB::transaction + AuditService + correlation_id.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class BookingService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать бронирование номера.
     *
     * @throws \DomainException
     */
    public function createBooking(
        int $hotelId,
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        int $numberOfGuests,
        int $guestId,
        int $tenantId,
        string $correlationId,
        ?string $specialRequests = null,
    ): Booking {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_booking_create',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $booking = $this->db->transaction(function () use (
            $hotelId,
            $roomTypeId,
            $checkInDate,
            $checkOutDate,
            $numberOfGuests,
            $guestId,
            $tenantId,
            $specialRequests,
            $correlationId,
        ) {
            $roomType = Room::findOrFail($roomTypeId);

            $checkInDt = Carbon::parse($checkInDate);
            $checkOutDt = Carbon::parse($checkOutDate);
            $nights = (int) $checkInDt->diffInDays($checkOutDt);

            if ($nights <= 0) {
                throw new \DomainException('Invalid check-in/out dates: checkout must be after checkin');
            }

            $subtotal = $roomType->base_price_per_night * $nights;
            $commission = (int) ($subtotal * 14 / 100);
            $cleaningFee = 50000;
            $total = $subtotal + $commission + $cleaningFee;

            return Booking::create([
                'tenant_id'        => $tenantId,
                'hotel_id'         => $hotelId,
                'room_type_id'     => $roomTypeId,
                'guest_id'         => $guestId,
                'confirmation_code' => strtoupper(Str::random(8)),
                'check_in_date'    => $checkInDate,
                'check_out_date'   => $checkOutDate,
                'number_of_guests' => $numberOfGuests,
                'nights_count'     => $nights,
                'subtotal_price'   => $subtotal,
                'cleaning_fee'     => $cleaningFee,
                'commission_price' => $commission,
                'total_price'      => $total,
                'booking_status'   => 'confirmed',
                'payment_status'   => 'pending',
                'special_requests' => $specialRequests,
                'correlation_id'   => $correlationId,
            ]);
        });

        $this->audit->log(
            action: 'hotel_booking_created',
            subjectType: Booking::class,
            subjectId: $booking->id,
            old: [],
            new: $booking->toArray(),
            correlationId: $correlationId,
        );

        $this->logger->info('Booking created successfully', [
            'booking_id'     => $booking->id,
            'hotel_id'       => $hotelId,
            'total_price'    => $booking->total_price,
            'correlation_id' => $correlationId,
        ]);

        return $booking;
    }

    /**
     * Подтвердить оплату бронирования.
     */
    public function confirmBooking(Booking $booking, string $correlationId): Booking
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_booking_confirm',
            amount: $booking->total_price,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $oldData = $booking->toArray();

        $this->db->transaction(function () use ($booking, $correlationId) {
            $booking->update([
                'payment_status' => 'paid',
                'paid_at'        => Carbon::now(),
            ]);
        });

        $this->audit->log(
            action: 'hotel_booking_confirmed',
            subjectType: Booking::class,
            subjectId: $booking->id,
            old: $oldData,
            new: $booking->fresh()->toArray(),
            correlationId: $correlationId,
        );

        $this->logger->info('Booking confirmed', [
            'booking_id'     => $booking->id,
            'correlation_id' => $correlationId,
        ]);

        return $booking->fresh();
    }

    /**
     * Отменить бронирование.
     */
    public function cancelBooking(Booking $booking, string $reason, string $correlationId): bool
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_booking_cancel',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $oldData = $booking->toArray();

        $this->db->transaction(function () use ($booking, $reason) {
            $booking->update([
                'booking_status' => 'cancelled',
                'metadata'       => array_merge($booking->metadata ?? [], [
                    'cancel_reason' => $reason,
                    'cancelled_at'  => Carbon::now()->toIso8601String(),
                ]),
            ]);
        });

        $this->audit->log(
            action: 'hotel_booking_cancelled',
            subjectType: Booking::class,
            subjectId: $booking->id,
            old: $oldData,
            new: $booking->fresh()->toArray(),
            correlationId: $correlationId,
        );

        $this->logger->info('Booking cancelled', [
            'booking_id'     => $booking->id,
            'reason'         => $reason,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }
}
