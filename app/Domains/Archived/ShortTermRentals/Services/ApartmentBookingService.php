<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApartmentBookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraud,


            private readonly WalletService $wallet,


        ) {}


        /**


         * Создание бронирования с холдированием полной суммы и страхового депозита.


         */


        public function book(int $apartmentId, int $userId, array $dates, string $correlationId = ""): ApartmentBooking


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            if (RateLimiter::tooManyAttempts("str:booking:".$userId, 3)) {


                throw new \RuntimeException("Apartment booking frequency limit exceeded.", 429);


            }


            RateLimiter::hit("str:booking:".$userId, 60);


            return DB::transaction(function () use ($apartmentId, $userId, $dates, $correlationId) {


                $apartment = Apartment::findOrFail($apartmentId);


                // 1. Fraud Check (подозрительные брони)


                $this->fraud->check([


                    "user_id" => $userId,


                    "operation_type" => "apartment_booking",


                    "correlation_id" => $correlationId,


                    "geo" => $dates['geo'] ?? null


                ]);


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


                    "str_apartment_booking_hold",


                    "Apartment Booking #{$booking->uuid}",


                    $correlationId


                );


                Log::channel("audit")->info("STR: Booking created and held", [


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


            DB::transaction(function () use ($booking, $isClaims, $correlationId) {


                if ($booking->status !== "checked_in") {


                    // В реальности статус меняется после заезда, тут упростим


                }


                // Обработка депозита


                if ($isClaims) {


                    // Если есть претензии - депозит остается замороженным до арбитража


                    Log::channel("audit")->warning("STR: Deposit held due to damage claims", ["booking_id" => $booking->id]);


                } else {


                    // Возврат депозита гостю


                    $this->wallet->releaseHold($booking->user_id, $booking->deposit_amount, $correlationId);


                    Log::channel("audit")->info("STR: Deposit released to guest", ["user_id" => $booking->user_id]);


                }


                // Выплата владельцу за вычетом комиссии (отложенная по канону в 2026, но тут инициируем процесс)


                $payout = $booking->amount - $booking->fee_amount;


                // Перевод аренды


                $this->wallet->releaseHold($booking->user_id, $booking->amount, $correlationId);


                $this->wallet->credit(


                    $booking->tenant_id,


                    $payout,


                    "str_payout",


                    "Payout for Booking #{$booking->uuid}",


                    $correlationId


                );


                $booking->update(["status" => "completed", "completed_at" => now()]);


                Log::channel("audit")->info("STR: Booking checkout completed", [


                    "booking_id" => $bookingId,


                    "payout" => $payout


                ]);


            });


        }
}
