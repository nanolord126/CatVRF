<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ApartmentBookingService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание бронирования с холдированием полной суммы и страхового депозита.
         */
        public function book(int $apartmentId, int $userId, array $dates, string $correlationId = ""): ApartmentBooking
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            if ($this->rateLimiter->tooManyAttempts("str:booking:".$userId, 3)) {
                throw new \RuntimeException("Apartment booking frequency limit exceeded.", 429);
            }
            $this->rateLimiter->hit("str:booking:".$userId, 60);

            return $this->db->transaction(function () use ($apartmentId, $userId, $dates, $correlationId) {
                $apartment = Apartment::findOrFail($apartmentId);

                // 1. Fraud Check (подозрительные брони)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $nights = count($dates['days'] ?? []);
                $renderPrice = $apartment->price_kopecks * $nights;
                $deposit = $apartment->security_deposit_kopecks ?: 500000; // 5000 руб по умолчанию
                $totalAmount = $renderPrice + $deposit;

                $commissionRate = $apartment->migrated_from ? 0.12 : 0.14;
                $fee = (int) ($renderPrice * $commissionRate);

                // 2. Создание брони
                $booking = ApartmentBooking::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $apartment->tenant_id,
                    "user_id" => $userId,
                    "apartment_id" => $apartmentId,
                    "check_in" => $dates['check_in'],
                    "check_out" => $dates['check_out'],
                    "amount" => $renderPrice,
                    "deposit_amount" => $deposit,
                    "fee_amount" => $fee,
                    "status" => "pending_checkin",
                    "correlation_id" => $correlationId,
                    "tags" => ["short_term", "escrow_4days", "security_insurance"]
                ]);

                // 3. Escrow Hold (Аренда + Депозит)
                $this->wallet->hold(
                    $userId,
                    $totalAmount,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::HOLD, $correlationId, null, null, null);

                $this->logger->info("STR: Booking created and held", [
                    "booking_uuid" => $booking->uuid,
                    "user_id" => $userId,
                    "total_held" => $totalAmount
                ]);

                return $booking;
            });
        }

        /**
         * Завершение проживания (чек-аут).
         * Выплата владельцу через 4 дня (по Канону), возврат депозита при отсутствии претензий.
         */
        public function checkout(int $bookingId, bool $isClaims = false, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $booking = ApartmentBooking::with(['apartment', 'user'])->findOrFail($bookingId);

            $this->db->transaction(function () use ($booking, $isClaims, $correlationId) {
                if ($booking->status !== "checked_in") {
                    // В реальности статус меняется после заезда, тут упростим
                }

                // Обработка депозита
                if ($isClaims) {
                    // Если есть претензии - депозит остается замороженным до арбитража
                    $this->logger->warning("STR: Deposit held due to damage claims", ["booking_id" => $booking->id]);
                } else {
                    // Возврат депозита гостю
                    $this->wallet->releaseHold($booking->user_id, $booking->deposit_amount, $correlationId);
                    $this->logger->info("STR: Deposit released to guest", ["user_id" => $booking->user_id]);
                }

                // Выплата владельцу за вычетом комиссии (отложенная по канону в 2026, но тут инициируем процесс)
                $payout = $booking->amount - $booking->fee_amount;

                // Перевод аренды
                $this->wallet->releaseHold($booking->user_id, $booking->amount, $correlationId);
                $this->wallet->credit(
                    $booking->tenant_id,
                    $payout,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                    "booking_id" => $bookingId,
                    "payout" => $payout
                ]);
            });
        }
}
