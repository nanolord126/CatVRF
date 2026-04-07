<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class BookingService
{

    private readonly string $correlationId;


    public function __construct(private WalletService $walletService,
            private readonly FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

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

            $this->logger->info('Initiating entertainment booking', [
                'event_uuid' => $event->uuid,
                'seats' => $seats,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            // 1. Fraud Check
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'entertainment_booking_init', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($event, $seats, $userId, $type, $correlationId) {
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

                $this->logger->info('Booking created (Hold state)', [
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

            $this->logger->info('Completing entertainment booking', [
                'booking_uuid' => $booking->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $this->db->transaction(function () use ($booking, $correlationId) {
                $lockingBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();

                // 1. Wallet Deduction (Debit) via WalletService
                // Предполагаем, что WalletService::debit() бросит исключение при нехватке средств
                $this->walletService->debit(
                    $booking->user_id,
                    $booking->total_amount_kopecks,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $correlationId, null, null, [
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

            $this->logger->warning('Cancelling entertainment booking', [
                'booking_uuid' => $booking->uuid,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->db->transaction(function () use ($booking, $correlationId) {
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

                $this->logger->info('Booking cancelled and capacity released', [
                    'booking_uuid' => $lockingBooking->uuid,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
