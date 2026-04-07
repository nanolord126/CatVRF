<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ClassBookingService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard)
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
                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $bookingId = $this->db->transaction(function () use ($classId, $userId, $correlationId) {
                    // Проверить лимит участников
                    $booked = $this->db->table('class_bookings')
                        ->where('class_id', $classId)
                        ->where('status', 'booked')
                        ->count();

                    $classData = $this->db->table('fitness_classes')->findOrFail($classId);
                    if ($booked >= $classData->max_participants) {
                        throw new \DomainException('Class is full');
                    }

                    $bookingId = $this->db->table('class_bookings')->insertGetId([
                        'class_id' => $classId,
                        'user_id' => $userId,
                        'status' => 'booked',
                        'correlation_id' => $correlationId,
                        'created_at' => now(),
                    ]);

                    $this->logger->info('Fitness class booked', [
                        'booking_id' => $bookingId,
                        'class_id' => $classId,
                        'user_id' => $userId,
                        'correlation_id' => $correlationId,
                    ]);

                    return $bookingId;
                });

                return $bookingId;
            } catch (\Throwable $e) {
                $this->logger->error('Fitness class booking failed', [
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
                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($bookingId, $correlationId) {
                    $this->db->table('class_bookings')
                        ->where('id', $bookingId)
                        ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                    $this->logger->info('Fitness class booking cancelled', [
                        'booking_id' => $bookingId,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return true;
            } catch (\Throwable $e) {
                $this->logger->error('Fitness class cancellation failed', [
                    'booking_id' => $bookingId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
