<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelBookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly PaymentService $payment,
            private readonly WalletService $wallet,
            private readonly DemandForecastService $forecast,
        ) {}

        /**
         * Создание бронирования тура (Пакет авиа + отель + гид).
         */
        public function bookTour(int $tourId, array $data, string $correlationId = ""): TravelBooking
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting — защита от DOS на бронирования
            if (RateLimiter::tooManyAttempts("travel:book:{$tourId}", 3)) {
                throw new \RuntimeException("Слишком много попыток бронирования. Подождите.", 429);
            }
            RateLimiter::hit("travel:book:{$tourId}", 3600);

            return DB::transaction(function () use ($tourId, $data, $correlationId) {
                $tour = TravelTour::with("agency")->findOrFail($tourId);

                // 2. Fraud Check (проверка на подозрительно дорогие туры или кардинг)
                $fraud = $this->fraud->check([
                    "user_id" => auth()->id() ?? 0,
                    "operation_type" => "travel_tour_book",
                    "correlation_id" => $correlationId,
                    "meta" => ["price" => $tour->price_kopecks, "tour_id" => $tourId]
                ]);

                if ($fraud["decision"] === "block") {
                    Log::channel("audit")->error("Travel Security Block", ["tour_id" => $tourId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
                }

                // 3. Создание брони
                $booking = TravelBooking::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $tour->tenant_id,
                    "tour_id" => $tourId,
                    "user_id" => auth()->id(),
                    "status" => "pending",
                    "total_price_kopecks" => $tour->price_kopecks,
                    "start_at" => Carbon::parse($data["start_at"]),
                    "end_at" => Carbon::parse($data["end_at"]),
                    "correlation_id" => $correlationId,
                    "tags" => ["international:" . ($data["is_international"] ? "yes" : "no"), "all_inclusive:yes"]
                ]);

                // 4. HOLD (резервация) отеля и перелета (симуляция внешней интеграции)
                Log::channel("audit")->info("Travel: External HOLD requested", ["booking_id" => $booking->id, "carrier" => "Aeroflot", "hotel" => "Hilton Riyadh"]);

                Log::channel("audit")->info("Travel: tour booked", ["booking_id" => $booking->id, "agency" => $tour->agency->id, "corr" => $correlationId]);

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

            DB::transaction(function () use ($booking, $correlationId) {
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

                Log::channel("audit")->info("Travel: payout processed", ["booking_id" => $booking->id, "payout" => $agencyPayout, "fee" => $platformFee]);
            });
        }

        /**
         * Прогноз спроса на туры (Demand Forecast).
         */
        public function predictTourDemand(int $agencyId): array
        {
            Log::channel("audit")->info("Travel: demand prediction initiated", ["agency" => $agencyId]);

            // Вызов DemandForecastService для планирования сезонов и акций
            return [
                "summer_2026_turkey" => $this->forecast->forecastBulk([404, 405], now(), now()->addMonths(6)),
                "winter_2026_dubai" => $this->forecast->forecastBulk([808, 809], now(), now()->addMonths(9))
            ];
        }

        /**
         * Подбор гида для тура.
         */
        public function assignGuide(int $bookingId, int $guideId): void
        {
            $booking = TravelBooking::findOrFail($bookingId);
            $guide = TravelGuide::findOrFail($guideId);

            $booking->update(["guide_id" => $guideId]);
            Log::channel("audit")->info("Travel: guide assigned to booking", ["guide_id" => $guideId, "booking" => $bookingId]);
        }
}
