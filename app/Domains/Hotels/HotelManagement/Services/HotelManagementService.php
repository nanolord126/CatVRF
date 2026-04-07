<?php

declare(strict_types=1);

/**
 * HotelManagementService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary
 *
 * @see https://catvrf.ru/docs/hotelmanagementservice
 */

namespace App\Domains\Hotels\HotelManagement\Services;

use App\Domains\Hotels\HotelManagement\Models\HotelBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

final readonly class HotelManagementService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    public function createBooking(
        int $hotelId,
        string $roomType,
        string $checkIn,
        string $checkOut,
        int $tenantId,
        string $correlationId = '',
    ): HotelBooking {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if ($this->rateLimiter->tooManyAttempts('hotel:book:' . ($this->guard->id() ?? 0), 11)) {
            throw new \RuntimeException('Too many', 429);
        }

        $this->rateLimiter->hit('hotel:book:' . ($this->guard->id() ?? 0), 3600);

        return $this->db->transaction(function () use ($hotelId, $roomType, $checkIn, $checkOut, $tenantId, $correlationId) {
            $fraudResult = $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'hotel_booking',
                amount: 0,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Security', 403);
            }

            $checkInDate = new \DateTimeImmutable($checkIn);
            $checkOutDate = new \DateTimeImmutable($checkOut);
            $nights = max(1, (int) $checkInDate->diff($checkOutDate)->days);
            $total = $nights * 500000;

            $booking = HotelBooking::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'hotel_id' => $hotelId,
                'guest_id' => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'room_type' => $roomType,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights_count' => $nights,
                'tags' => ['hotel' => true],
            ]);

            $this->logger->info('Hotel booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    public function completeBooking(int $bookingId, string $correlationId = ''): HotelBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId) {
            $booking = HotelBooking::findOrFail($bookingId);

            if ($booking->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $booking->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                $booking->tenant_id,
                $booking->payout_kopecks,
                'hotel_payout',
                $correlationId,
            );

            $this->logger->info('Hotel booking completed', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    public function cancelBooking(int $bookingId, string $correlationId = ''): HotelBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId) {
            $booking = HotelBooking::findOrFail($bookingId);

            if ($booking->status === 'completed') {
                throw new \RuntimeException('Cannot cancel', 400);
            }

            $previousPaymentStatus = $booking->payment_status;

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousPaymentStatus === 'completed') {
                $this->wallet->credit(
                    $booking->tenant_id,
                    $booking->total_kopecks,
                    'hotel_refund',
                    $correlationId,
                );
            }

            $this->logger->info('Hotel booking cancelled', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    public function getBooking(int $bookingId): HotelBooking
    {
        return HotelBooking::findOrFail($bookingId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, HotelBooking>
     */
    public function getUserBookings(int $guestId): \Illuminate\Database\Eloquent\Collection
    {
        return HotelBooking::where('guest_id', $guestId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
