<?php declare(strict_types=1);

namespace App\Domains\Archived\MusicAndInstruments\Music\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicBookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Create a studio booking.


         */


        public function bookStudio(int $studioId, Carbon $startsAt, Carbon $endsAt, int $userId): MusicBooking


        {


            FraudControlService::check();


            return DB::transaction(function () use ($studioId, $startsAt, $endsAt, $userId) {


                $studio = MusicStudio::lockForUpdate()->findOrFail($studioId);


                $correlationId = (string) Str::uuid();


                // Check if studio is available


                $isBooked = MusicBooking::where('bookable_id', $studioId)


                    ->where('bookable_type', MusicStudio::class)


                    ->where('status', 'confirmed')


                    ->where(function ($query) use ($startsAt, $endsAt) {


                        $query->whereBetween('starts_at', [$startsAt, $endsAt])


                            ->orWhereBetween('ends_at', [$startsAt, $endsAt]);


                    })


                    ->exists();


                if ($isBooked) {


                    throw new \Exception('Studio is already booked for this period.');


                }


                $durationMinutes = $startsAt->diffInMinutes($endsAt);


                $totalPriceCents = (int) ($studio->price_per_hour_cents * ($durationMinutes / 60));


                $booking = MusicBooking::create([


                    'user_id' => $userId,


                    'bookable_id' => $studioId,


                    'bookable_type' => MusicStudio::class,


                    'starts_at' => $startsAt,


                    'ends_at' => $endsAt,


                    'total_price_cents' => $totalPriceCents,


                    'status' => 'confirmed',


                    'correlation_id' => $correlationId,


                ]);


                Log::channel('audit')->info('Music studio booked', [


                    'booking_id' => $booking->id,


                    'studio_id' => $studioId,


                    'total_price' => $totalPriceCents,


                    'correlation_id' => $correlationId,


                ]);


                return $booking;


            });


        }


        /**


         * Check current availability for specific date.


         */


        public function getAvailability(string $bookableType, int $bookableId, Carbon $date): Collection


        {


            return MusicBooking::where('bookable_type', $bookableType)


                ->where('bookable_id', $bookableId)


                ->whereDate('starts_at', $date)


                ->where('status', 'confirmed')


                ->get();


        }


        /**


         * Cancel a booking.


         */


        public function cancelBooking(int $bookingId, int $userId): void


        {


            $booking = MusicBooking::where('user_id', $userId)->findOrFail($bookingId);


            if ($booking->status === 'cancelled') {


                throw new \Exception('Booking is already cancelled.');


            }


            $booking->update([


                'status' => 'cancelled',


            ]);


            Log::channel('audit')->info('Music booking cancelled', [


                'booking_id' => $booking->id,


                'user_id' => $userId,


                'correlation_id' => $booking->correlation_id,


            ]);


        }
}
