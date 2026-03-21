<?php declare(strict_types=1);

namespace App\Domains\Fitness\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ClassBookingService
{
    public function __construct()
    {
    }

    /**
     * Забронировать занятие в фитнес-центре
     */
    public function bookFitnessClass(
        int $classId,
        int $userId,
        string $correlationId,
    ): int {
        try {
            $bookingId = DB::transaction(function () use ($classId, $userId, $correlationId) {
                // Проверить лимит участников
                $booked = DB::table('class_bookings')
                    ->where('class_id', $classId)
                    ->where('status', 'booked')
                    ->count();

                $classData = DB::table('fitness_classes')->findOrFail($classId);
                if ($booked >= $classData->max_participants) {
                    throw new \Exception('Class is full');
                }

                $bookingId = DB::table('class_bookings')->insertGetId([
                    'class_id' => $classId,
                    'user_id' => $userId,
                    'status' => 'booked',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Fitness class booked', [
                    'booking_id' => $bookingId,
                    'class_id' => $classId,
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);

                return $bookingId;
            });

            return $bookingId;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Fitness class booking failed', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить бронирование класса
     */
    public function cancelBooking(int $bookingId, string $correlationId): bool
    {
        try {
            DB::transaction(function () use ($bookingId, $correlationId) {
                DB::table('class_bookings')
                    ->where('id', $bookingId)
                    ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                Log::channel('audit')->info('Fitness class booking cancelled', [
                    'booking_id' => $bookingId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Fitness class cancellation failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
