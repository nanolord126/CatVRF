<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class TravelBookingService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly InventoryManagementService $inventoryService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Бронирование тура с проверкой доступных мест.
     */
    public function bookTour(int $tourId, int $participants, string $correlationId): TravelBooking
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'travel_booking',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tourId, $participants, $correlationId): TravelBooking {
            $tour = TravelTour::lockForUpdate()->findOrFail($tourId);

            if ($tour->status !== 'active' && $tour->status !== 'published') {
                throw new \RuntimeException("Tour {$tourId} is not available for booking.");
            }

            $availableSlots = $tour->max_participants - $tour->current_participants;
            if ($participants > $availableSlots) {
                throw new \RuntimeException("Not enough slots. Available: {$availableSlots}, requested: {$participants}.");
            }

            $totalPrice = $tour->base_price * $participants;
            $platformFee = (int) ($totalPrice * 0.14);

            $booking = TravelBooking::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tour->tenant_id,
                'tour_id' => $tourId,
                'client_id' => $this->guard->id(),
                'participants' => $participants,
                'total_price' => $totalPrice,
                'platform_fee' => $platformFee,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'correlation_id' => $correlationId,
                'tags' => [
                    'destination' => $tour->destination_country,
                    'duration_days' => $tour->duration_days,
                ],
            ]);

            $tour->increment('current_participants', $participants);

            $this->logger->info('Travel booking created', [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'tour_id' => $tourId,
                'participants' => $participants,
                'total_price' => $totalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Подтверждение оплаты бронирования.
     */
    public function confirmPayment(int $bookingId, string $correlationId): TravelBooking
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'travel_payment_confirm',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($bookingId, $correlationId): TravelBooking {
            $booking = TravelBooking::lockForUpdate()->findOrFail($bookingId);

            if ($booking->payment_status !== 'pending') {
                throw new \RuntimeException("Booking {$bookingId} payment is not pending.");
            }

            $booking->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Travel booking payment confirmed', [
                'booking_id' => $booking->id,
                'total_price' => $booking->total_price,
                'correlation_id' => $correlationId,
            ]);

            return $booking->refresh();
        });
    }

    /**
     * Отмена бронирования с возвратом мест и средств.
     */
    public function cancelBooking(int $bookingId, string $reason, string $correlationId): TravelBooking
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'travel_booking_cancel',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($bookingId, $reason, $correlationId): TravelBooking {
            $booking = TravelBooking::with('tour')->lockForUpdate()->findOrFail($bookingId);

            if ($booking->status === 'completed') {
                throw new \RuntimeException("Cannot cancel a completed booking.");
            }

            if ($booking->payment_status === 'completed') {
                $this->wallet->credit(
                    userId: $booking->client_id,
                    amount: $booking->total_price,
                    type: 'travel_refund',
                    reason: "Travel booking #{$booking->id} cancelled: {$reason}",
                    correlationId: $correlationId,
                );
            }

            $booking->tour->decrement('current_participants', $booking->participants);

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => $booking->payment_status === 'completed' ? 'refunded' : $booking->payment_status,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Travel booking cancelled', [
                'booking_id' => $booking->id,
                'participants_released' => $booking->participants,
                'reason' => $reason,
                'refund_issued' => $booking->payment_status === 'refunded',
                'correlation_id' => $correlationId,
            ]);

            return $booking->refresh();
        });
    }

    /**
     * Завершение тура и выплата туроператору.
     */
    public function completeTourPayout(int $bookingId, string $correlationId): TravelBooking
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'travel_payout',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($bookingId, $correlationId): TravelBooking {
            $booking = TravelBooking::with('tour')->lockForUpdate()->findOrFail($bookingId);

            if ($booking->status !== 'confirmed') {
                throw new \RuntimeException("Booking {$bookingId} is not confirmed for payout.");
            }

            $payoutAmount = $booking->total_price - $booking->platform_fee;

            $this->wallet->credit(
                userId: $booking->tour->tour_operator_id,
                amount: $payoutAmount,
                type: 'travel_tour_payout',
                reason: "Tour booking #{$booking->id} completed",
                correlationId: $correlationId,
            );

            $booking->update([
                'status' => 'completed',
                'payout_amount' => $payoutAmount,
                'completed_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Travel tour payout completed', [
                'booking_id' => $booking->id,
                'payout_amount' => $payoutAmount,
                'tour_operator_id' => $booking->tour->tour_operator_id,
                'correlation_id' => $correlationId,
            ]);

            return $booking->refresh();
        });
    }
}
