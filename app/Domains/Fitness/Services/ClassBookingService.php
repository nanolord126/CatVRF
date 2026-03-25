<?php declare(strict_types=1);

namespace App\Domains\Fitness\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

final class ClassBookingService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
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
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $bookingId = $this->db->transaction(function () use ($classId, $userId, $correlationId) {
                // Проверить лимит участников
                $booked = $this->db->table('class_bookings')
                    ->where('class_id', $classId)
                    ->where('status', 'booked')
                    ->count();

                $classData = $this->db->table('fitness_classes')->findOrFail($classId);
                if ($booked >= $classData->max_participants) {
                    throw new \Exception('Class is full');
                }

                $bookingId = $this->db->table('class_bookings')->insertGetId([
                    'class_id' => $classId,
                    'user_id' => $userId,
                    'status' => 'booked',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                $this->log->channel('audit')->info('Fitness class booked', [
                    'booking_id' => $bookingId,
                    'class_id' => $classId,
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);

                return $bookingId;
            });

            return $bookingId;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Fitness class booking failed', [
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
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $this->db->transaction(function () use ($bookingId, $correlationId) {
                $this->db->table('class_bookings')
                    ->where('id', $bookingId)
                    ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                $this->log->channel('audit')->info('Fitness class booking cancelled', [
                    'booking_id' => $bookingId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Fitness class cancellation failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
