<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;

use App\Domains\EventPlanning\Entertainment\Models\Booking;
use App\Domains\EventPlanning\Entertainment\Models\Event;
use App\Domains\EventPlanning\Entertainment\Models\Ticket;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — BOOKING SERVICE
 * 1. final readonly class
 * 2. DB::transaction() для всех мутаций
 * 3. FraudControlService::check()
 * 4. Audit-logging (audit channel)
 * 5. Correlation ID tracking
 */
final readonly class BookingService
{
    public function __construct(
        private WalletService $walletService,
        private FraudControlService $fraudControl,
        private string $correlationId = ''
    ) {
    }

    private function getCorrelationId(): string
    {
        return $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Создать бронирование (Hold на 20 мин)
     */
    public function initiateBooking(Event $event, array $seats, ?int $userId, string $type = 'b2c'): Booking
    {
        $correlationId = $this->getCorrelationId();

        Log::channel('audit')->info('Initiating entertainment booking', [
            'event_uuid' => $event->uuid,
            'seats' => $seats,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        // 1. Fraud Check
        $this->fraudControl->check([
            'user_id' => $userId,
            'operation' => 'entertainment_booking_init',
            'event_id' => $event->id,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($event, $seats, $userId, $type, $correlationId) {
            // 2. Lock check for capacity
            $eventRefresh = Event::where('id', $event->id)->lockForUpdate()->first();
            
            if (!$eventRefresh->hasCapacity(count($seats))) {
                throw new \RuntimeException("Insufficient capacity for event: {$eventRefresh->title}");
            }

            // 3. Create Booking (Pending status)
            /** @var Booking $booking */
            $booking = Booking::create([
                'tenant_id' => $eventRefresh->tenant_id,
                'event_id' => $eventRefresh->id,
                'user_id' => $userId,
                'type' => $type,
                'status' => 'pending',
                'total_amount_kopecks' => $eventRefresh->base_price_kopecks * count($seats),
                'selected_seats' => $seats,
                'correlation_id' => $correlationId,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            // 4. Update capacity
            $eventRefresh->decrementCapacity(count($seats));

            Log::channel('audit')->info('Booking created (Hold state)', [
                'booking_uuid' => $booking->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Оплата и подтверждение бронирования
     */
    public function completeBooking(Booking $booking): bool
    {
        $correlationId = $this->getCorrelationId();

        if ($booking->status === 'paid') {
            return true;
        }

        if ($booking->isExpired()) {
            $this->cancelBooking($booking, 'Expired during completion');
            throw new \RuntimeException("Booking expired and cannot be completed");
        }

        Log::channel('audit')->info('Completing entertainment booking', [
            'booking_uuid' => $booking->uuid,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($booking, $correlationId) {
            $lockingBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();

            // 1. Wallet Deduction (Debit) via WalletService
            // Предполагаем, что WalletService::debit() бросит исключение при нехватке средств
            $this->walletService->debit(
                $booking->user_id, 
                $booking->total_amount_kopecks, 
                'Entertainment booking payout: ' . $booking->uuid,
                $correlationId
            );

            // 2. Update status
            $lockingBooking->markAsPaid();

            // 3. Issue Tickets
            $this->generateTickets($lockingBooking);

            Log::channel('audit')->info('Booking completed and tickets issued', [
                'booking_uuid' => $lockingBooking->uuid,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Генерация билетов для бронирования
     */
    private function generateTickets(Booking $booking): void
    {
        $seats = $booking->getSelectedSeatsArray();
        
        foreach ($seats as $seatLabel) {
            Ticket::create([
                'booking_id' => $booking->id,
                'event_id' => $booking->event_id,
                'tenant_id' => $booking->tenant_id,
                'ticket_number' => 'ENT-' . strtoupper(Str::random(10)),
                'seat_label' => (string)$seatLabel,
                'correlation_id' => $booking->correlation_id,
            ]);
        }
    }

    /**
     * Отмена бронирования (возврат мест)
     */
    public function cancelBooking(Booking $booking, string $reason = ''): void
    {
        $correlationId = $this->getCorrelationId();

        Log::channel('audit')->warning('Cancelling entertainment booking', [
            'booking_uuid' => $booking->uuid,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        DB::transaction(function () use ($booking, $correlationId) {
            $lockingBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
            
            if ($lockingBooking->status === 'cancelled') {
                return;
            }

            // 1. Release capacity
            $event = Event::where('id', $lockingBooking->event_id)->lockForUpdate()->first();
            if ($event) {
                $event->incrementCapacity($lockingBooking->getTicketCount());
            }

            // 2. Mark cancelled
            $lockingBooking->markAsCancelled();

            Log::channel('audit')->info('Booking cancelled and capacity released', [
                'booking_uuid' => $lockingBooking->uuid,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
