<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class BookingService
{

    public function __construct(private WalletService $wallet,
            private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создать бронирование с транзакцией и контролем.
         */
        public function createBooking(BookingDto $dto): Booking
        {
            $this->logger->info('Travel booking attempt started', [
                'user_id' => $dto->userId,
                'bookable_type' => $dto->bookableType,
                'bookable_id' => $dto->bookableId,
                'correlation_id' => $dto->correlationId
            ]);

            // 1. Предварительная проверка фрода (Слой 6)
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'travel_booking', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($dto) {
                // 2. Блокировка объекта для проверки наличия мест
                $bookableClass = $this->resolveBookableClass($dto->bookableType);
                $bookable = $bookableClass::where('id', $dto->bookableId)->lockForUpdate()->firstOrFail();

                // 3. Проверка на Trip
                if ($bookable instanceof Trip && !$bookable->isAvailable($dto->slotsCount)) {
                    throw new \RuntimeException('Недостаточно свободных мест на выбранный выезд');
                }

                // 4. Цена
                $totalPrice = $bookable->price * $dto->slotsCount;

                // 5. Запись бронирования
                $booking = Booking::create([
                    'user_id' => $dto->userId,
                    'bookable_type' => $bookableClass,
                    'bookable_id' => $dto->bookableId,
                    'slots_count' => $dto->slotsCount,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'idempotency_key' => $dto->idempotencyKey ?? (string) Str::uuid(),
                    'correlation_id' => $dto->correlationId,
                    'metadata' => $dto->metadata
                ]);

                // 6. Слоты
                if ($bookable instanceof Trip) {
                    $bookable->increment('booked_slots', $dto->slotsCount);
                }

                $this->logger->info('Travel booking created successfully', [
                    'booking_id' => $booking->id,
                    'total_price' => $totalPrice,
                    'correlation_id' => $dto->correlationId
                ]);

                return $booking;
            });
        }

        /**
         * Оплата бронирования.
         */
        public function payBooking(int $bookingId, string $correlationId): bool
        {
            return $this->db->transaction(function () use ($bookingId, $correlationId) {
                $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

                if ($booking->status === 'paid') return true;

                // Списание (Слой 3 + Wallet)
                $this->wallet->debit(
                    $booking->user_id,
                    $booking->total_price,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $correlationId, null, null, [
                        'booking_id' => $booking->id,
                        'correlation_id' => $correlationId
                    ]);

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid'
                ]);

                return true;
            });
        }

        /**
         * Отмена бронирования.
         */
        public function cancelBooking(int $bookingId, string $reason, string $correlationId): bool
        {
            return $this->db->transaction(function () use ($bookingId, $reason, $correlationId) {
                $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

                if (in_array($booking->status, ['cancelled', 'completed'])) return false;

                // Возврат
                if ($booking->payment_status === 'paid') {
                    $this->wallet->credit($booking->user_id, $booking->total_price, \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, [
                        'booking_id' => $booking->id,
                        'reason' => $reason,
                        'correlation_id' => $correlationId
                    ]);
                }

                // Слоты
                if ($booking->bookable instanceof Trip) {
                    $booking->bookable->decrement('booked_slots', $booking->slots_count);
                }

                $booking->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($booking->metadata ?? [], ['cancel_reason' => $reason])
                ]);

                return true;
            });
        }

        private function resolveBookableClass(string $type): string
        {
            return match ($type) {
                'trip', 'Trip' => Trip::class,
                'excursion', 'Excursion' => Excursion::class,
                default => throw new \InvalidArgumentException('Unknown bookable type: ' . $type)
            };
        }
}
