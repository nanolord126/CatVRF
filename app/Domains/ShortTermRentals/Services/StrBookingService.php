<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class StrBookingService
{

    public function __construct(private readonly StrAvailabilityService $availabilityService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создание бронирования с холдом залога
         */
        public function createBooking(int $apartmentId, int $userId, Carbon $checkIn, Carbon $checkOut, bool $isB2B = false): StrBooking
        {
            $correlationId = $this->request->header('X-Correlation-ID', (string) Str::uuid());

            // 1. Предварительная проверка фрода
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'str_booking', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($apartmentId, $userId, $checkIn, $checkOut, $isB2B, $correlationId) {
                // 2. Блокуем апартамент на чтение/запись
                $apartment = StrApartment::lockForUpdate()->findOrFail($apartmentId);

                // 3. Проверяем доступность еще раз внутри транзакции
                if (!$this->availabilityService->isAvailable($apartmentId, $checkIn, $checkOut)) {
                    throw new \RuntimeException('Апартамент недоступен на выбранные даты.');
                }

                // 4. Расчет стоимости
                $prices = $this->availabilityService->getPrices($apartmentId, $checkIn, $checkOut, $isB2B);
                $totalPrice = $prices->sum('price');
                $depositAmount = $apartment->deposit_amount;

                // 5. Создание записи в БД
                $booking = StrBooking::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'business_group_id' => $apartment->property->business_group_id,
                    'apartment_id' => $apartmentId,
                    'user_id' => $userId,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => 'pending',
                    'total_price' => $totalPrice,
                    'deposit_amount' => $depositAmount,
                    'deposit_status' => 'pending',
                    'payment_status' => 'pending',
                    'payout_at' => $checkOut->copy()->addDays(4), // Выплата через 4 дня после выезда
                    'is_b2b' => $isB2B,
                    'correlation_id' => $correlationId,
                    'metadata' => [
                        'daily_prices' => $prices->toArray(),
                    ],
                ]);

                // 6. Логирование инициации
                $this->logger->info('ShortTermRental Booking Initiated', [
                    'booking_id' => $booking->id,
                    'total_price' => $totalPrice,
                    'deposit_amount' => $depositAmount,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            });
        }

        /**
         * Подтверждение оплаты и холд залога
         */
        public function confirmPayment(int $bookingId, string $correlationId): void
        {
            $this->db->transaction(function () use ($bookingId, $correlationId) {
                $booking = StrBooking::lockForUpdate()->findOrFail($bookingId);

                if ($booking->status !== 'pending') {
                    throw new \RuntimeException('Бронирование уже обработано.');
                }

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'deposit_status' => 'held', // Залог успешно захолдирован
                    'correlation_id' => $correlationId,
                ]);

                // 7. Очистка кэша доступности
                $this->availabilityService->invalidateCache($booking->apartment_id);

                $this->logger->info('ShortTermRental Booking Confirmed & Deposit Held', [
                    'booking_id' => $bookingId,
                    'deposit_amount' => $booking->deposit_amount,
                    'correlation_id' => $correlationId,
                ]);

                // Можно отправить ивент
                // event(new BookingConfirmed($booking));
            });
        }

        /**
         * Возврат залога гостю
         */
        public function releaseDeposit(int $bookingId, string $correlationId): void
        {
            $this->db->transaction(function () use ($bookingId, $correlationId) {
                $booking = StrBooking::lockForUpdate()->findOrFail($bookingId);

                if (!$booking->isDepositHeld()) {
                    throw new \RuntimeException('Залог не был захолдирован.');
                }

                $booking->update([
                    'deposit_status' => 'released',
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('ShortTermRental Deposit Released', [
                    'booking_id' => $bookingId,
                    'amount' => $booking->deposit_amount,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
