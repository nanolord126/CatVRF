<?php

declare(strict_types=1);

/**
 * LodgingService — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/lodgingservice
 */

namespace App\Domains\Hotels\Lodging\Services;

use App\Domains\Hotels\Lodging\Models\LodgingBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

final readonly class LodgingService
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
        int $lodgeId,
        string $checkIn,
        string $checkOut,
        int $tenantId,
        string $correlationId = '',
    ): LodgingBooking {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if ($this->rateLimiter->tooManyAttempts('lodging:book:' . ($this->guard->id() ?? 0), 10)) {
            throw new \RuntimeException('Too many', 429);
        }

        $this->rateLimiter->hit('lodging:book:' . ($this->guard->id() ?? 0), 3600);

        return $this->db->transaction(function () use ($lodgeId, $checkIn, $checkOut, $tenantId, $correlationId) {
            $fraudResult = $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'lodging_booking',
                amount: 0,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Security', 403);
            }

            $checkInDate = new \DateTimeImmutable($checkIn);
            $checkOutDate = new \DateTimeImmutable($checkOut);
            $nights = max(1, (int) $checkInDate->diff($checkOutDate)->days);
            $total = $nights * 400000;

            $booking = LodgingBooking::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'lodge_id' => $lodgeId,
                'client_id' => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'tags' => ['lodging' => true],
            ]);

            $this->logger->info('Lodging booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    public function completeBooking(int $bookingId, string $correlationId = ''): LodgingBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId) {
            $booking = LodgingBooking::findOrFail($bookingId);

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
                'lodging_payout',
                $correlationId,
            );

            $this->logger->info('Lodging booking completed', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    public function cancelBooking(int $bookingId, string $correlationId = ''): LodgingBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId) {
            $booking = LodgingBooking::findOrFail($bookingId);

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
                    'lodging_refund',
                    $correlationId,
                );
            }

            $this->logger->info('Lodging booking cancelled', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    public function getBooking(int $bookingId): LodgingBooking
    {
        return LodgingBooking::findOrFail($bookingId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, LodgingBooking>
     */
    public function getUserBookings(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return LodgingBooking::where('client_id', $clientId)
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
