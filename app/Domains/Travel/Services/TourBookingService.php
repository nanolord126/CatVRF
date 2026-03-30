<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TourBookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct()
        {
        }

        public function bookTour(
            int $tourId,
            int $userId,
            int $personCount,
            string $startDate,
            string $correlationId,
        ): int {


            try {
                $bookingId = DB::transaction(function () use ($tourId, $userId, $personCount, $startDate, $correlationId) {
                    $tour = DB::table('travel_tours')->findOrFail($tourId);

                    $bookingId = DB::table('travel_bookings')->insertGetId([
                        'tour_id' => $tourId,
                        'user_id' => $userId,
                        'person_count' => $personCount,
                        'start_date' => $startDate,
                        'total_price' => $tour->price_per_person * $personCount,
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                        'created_at' => now(),
                    ]);

                    Log::channel('audit')->info('Tour booked', [
                        'booking_id' => $bookingId,
                        'tour_id' => $tourId,
                        'persons' => $personCount,
                        'correlation_id' => $correlationId,
                    ]);

                    return $bookingId;
                });

                return $bookingId;
            } catch (\Exception $e) {
                Log::channel('audit')->error('Tour booking failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
