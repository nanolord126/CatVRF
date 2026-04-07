<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TravelBookingService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly PaymentService $payment,
            private readonly WalletService $wallet,
            private readonly DemandForecastService $forecast,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание бронирования тура (Пакет авиа + отель + гид).
         */
        public function bookTour(int $tourId, array $data, string $correlationId = ""): TravelBooking
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting — защита от DOS на бронирования
            if ($this->rateLimiter->tooManyAttempts("travel:book:{$tourId}", 3)) {
                throw new \RuntimeException("Слишком много попыток бронирования. Подождите.", 429);
            }
            $this->rateLimiter->hit("travel:book:{$tourId}", 3600);

            return $this->db->transaction(function () use ($tourId, $data, $correlationId) {
                $tour = TravelTour::with("agency")->findOrFail($tourId);

                // 2. Fraud Check (проверка на подозрительно дорогие туры или кардинг)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->error("Travel Security Block", ["tour_id" => $tourId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
                }

                // 3. Создание брони
                $booking = TravelBooking::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $tour->tenant_id,
                    "tour_id" => $tourId,
                    "user_id" => $this->guard->id(),
                    "status" => "pending",
                    "total_price_kopecks" => $tour->price_kopecks,
                    "start_at" => Carbon::parse($data["start_at"]),
                    "end_at" => Carbon::parse($data["end_at"]),
                    "correlation_id" => $correlationId,
                    "tags" => ["international:" . ($data["is_international"] ? "yes" : "no"), "all_inclusive:yes"]
                ]);

                // 4. HOLD (резервация) отеля и перелета (симуляция внешней интеграции)
                $this->logger->info("Travel: External HOLD requested", ["booking_id" => $booking->id, "carrier" => "Aeroflot", "hotel" => "Hilton Riyadh"]);

                $this->logger->info("Travel: tour booked", ["booking_id" => $booking->id, "agency" => $tour->agency->id, "corr" => $correlationId]);

                return $booking;
            });
        }

        /**
         * Завершение поездки (выплата агентству через 4-7 дней после окончания).
         */
        public function finishTour(int $bookingId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $booking = TravelBooking::with("tour.agency")->findOrFail($bookingId);

            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    "status" => "completed",
                    "finished_at" => now()
                ]);

                // 5. Расчет комиссии платформы (14% стандарт / 12% при миграции с Travelata)
                $multiplier = $booking->tour->agency->is_migrated ? 0.12 : 0.14;
                $total = $booking->total_price_kopecks;
                $platformFee = (int) ($total * $multiplier);
                $agencyPayout = $total - $platformFee;

                // Выплата турагентству
                $this->wallet->credit(
                    userId: $booking->tour->agency->owner_id,
                    amount: $agencyPayout,
                    type: "travel_payout",
                    reason: "Tour completed: {$booking->id}",
                    correlationId: $correlationId
                );

                $this->logger->info("Travel: payout processed", ["booking_id" => $booking->id, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ["agency" => $agencyId]);

            // Вызов DemandForecastService для планирования сезонов и акций
            return [
                "summer_2026_turkey" => $this->forecast->forecastBulk([404, 405], now(), now()->addMonths(6)),
                \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ["guide_id" => $guideId, "booking" => $bookingId]);
        }
}
