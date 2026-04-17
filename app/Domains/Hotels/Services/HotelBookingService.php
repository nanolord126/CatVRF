<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Models\B2BContract;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Domains\Wallet\Services\AtomicWalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис бронирования отелей.
 * Layer 3: Services — CatVRF 2026
 *
 * Создание, подтверждение и отмена бронирований.
 * Ценообразование B2C/B2B, FraudCheck, Wallet, Audit.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class HotelBookingService
{
    public function __construct(
        private FraudControlService $fraud,
        private WtomicWalletService $atomicWallet,
        private AalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать бронирование номера.
     *
     * @param array<string, mixed> $data
     *
     * @throws \DomainException
     */
    public function initiateBooking(array $data, int $tenantId, string $correlationId): Booking
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_booking_init',
            amount: (int) ($data['total_price'] ?? 0),
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $tenantId, $userId, $correlationId) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);
            $hotel = $room->hotel;

            if (!$room->is_available || $room->total_stock <= 0) {
                throw new \DomainException('Room is not available or out of stock');
            }

            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $nights = (int) $checkIn->diffInDays($checkOut);

            if ($nights < ($room->min_stay_days ?? 1)) {
                throw new \DomainException("Minimum stay for this room is {$room->min_stay_days} nights");
            }

            $isB2B = (bool) ($data['is_b2b'] ?? false);
            $contractId = $data['contract_id'] ?? null;
            $totalPrice = $this->calculateTotalPrice($room, $nights, $isB2B, $contractId);

            $booking = Booking::create([
                'uuid'              => (string) Str::uuid(),
                'tenant_id'         => $tenantId,
                'business_group_id' => $hotel->business_group_id,
                'hotel_id'          => $hotel->id,
                'room_id'           => $room->id,
                'user_id'           => $userId,
                'check_in'          => $checkIn,
                'check_out'         => $checkOut,
                'status'            => 'pending',
                'total_price'       => $totalPrice,
                'currency'          => 'RUB',
                'payment_status'    => 'pending',
                'is_b2b'            => $isB2B,
                'contract_id'       => $contractId,
                'correlation_id'    => $correlationId,
                'metadata'          => [
                    'nights'          => $nights,
                    'price_per_night' => $totalPrice / $nights,
                ],
            ]);

            $room->decrement('total_stock');

            $this->audit->log(
                action: 'hotel_booking_created',
                subjectType: Booking::class,
                subjectId: $booking->id,
                old: [],
                new: $booking->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel booking created', [
                'booking_id'     => $booking->id,
                'hotel_id'       => $hotel->id,
                'room_id'        => $room->id,
                'total_price'    => $totalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Подтвердить бронирование после оплаты.
     */
    public function confirmBooking(int $bookingId, string $paymentId, string $correlationId): void
    {
        $this->db->transaction(function () use ($bookingId, $paymentId, $correlationId) {
            $booking = Booking::lockForUpdate()->findOrFail($bookingId);

            if ($booking->status !== 'pending') {
                throw new \DomainException('Booking is already processed');
            }

            $oldData = $booking->toArray();

            $booking->update([
                'status'         => 'confirmed',
                'payment_status' => 'paid',
                'metadata'       => array_merge($booking->metadata ?? [], [
                    'payment_id'   => $paymentId,
                    'confirmed_at' => Carbon::now()->toIso8601String(),
                ]),
                'payout_at' => Carbon::now()->addDays(4),
            ]);

            $this->audit->log(
                action: 'hotel_booking_confirmed',
                subjectType: Booking::class,
                subjectId: $bookingId,
                old: $oldData,
                new: $booking->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel booking confirmed', [
                'booking_id'     => $bookingId,
                'payment_id'     => $paymentId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Отменить бронирование и вернуть сток.
     */
    public function cancelBooking(int $bookingId, string $reason, string $correlationId): void
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

        $this->db->transaction(function () use ($bookingId, $reason, $correlationId) {
            $booking = Booking::lockForUpdate()->findOrFail($bookingId);

            if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
                throw new \DomainException('Booking cannot be cancelled in status: ' . $booking->status);
            }

            $oldData = $booking->toArray();

            $booking->update([
                'status'   => 'cancelled',
                'metadata' => array_merge($booking->metadata ?? [], [
                    'cancel_reason'  => $reason,
                    'cancelled_at'   => Carbon::now()->toIso8601String(),
                ]),
            ]);

            $room = Room::findOrFail($booking->room_id);
            $room->increment('total_stock');

            if ($booking->payment_status === 'paid') {
                $wallet = Wallet::where('user_id', $booking->user_id)
                    ->where('tenant_id', $booking->tenant_id)
                    ->first();
                
                if ($wallet !== null) {
                    $this->atomicWallet->credit(
                        walletId: $wallet->id,
                        amount: $booking->total_price,
                        type: \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                        correlationId: $correlationId,
                        sourceType: 'hotel_booking',
                        sourceId: $booking->id,
                    );
                }
            }

            $this->audit->log(
                action: 'hotel_booking_cancelled',
                subjectType: Booking::class,
                subjectId: $bookingId,
                old: $oldData,
                new: $booking->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel booking cancelled', [
                'booking_id'     => $bookingId,
                'reason'         => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Рассчитать полную стоимость проживания с учётом B2B-скидок.
     */
    private function calculateTotalPrice(Room $room, int $nights, bool $isB2B, ?int $contractId): int
    {
        $pricePerNight = $isB2B
            ? ($room->base_price_b2b ?? $room->base_price_b2c)
            : $room->base_price_b2c;

        if ($isB2B && $contractId !== null) {
            $contract = B2BContract::find($contractId);

            if ($contract !== null && $contract->isValid()) {
                $pricePerNight = (int) ($pricePerNight * (1 - $contract->discount_percent / 100));
            }
        }

        return $pricePerNight * $nights;
    }
}
