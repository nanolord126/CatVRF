<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Services\FraudControlService;
use Psr\Log\LoggerInterface;

/**
 * Сервис бронирования туров.
 *
 * Обрабатывает создание бронирований туристических туров.
 * Все мутации оборачиваются в транзакцию с fraud-проверкой и audit-логированием.
 *
 * @package App\Domains\Travel\Services
 */
final readonly class TourBookingService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Забронировать тур для пользователя.
     *
     * @param int $tourId Идентификатор тура
     * @param int $userId Идентификатор пользователя
     * @param int $personCount Количество участников
     * @param string $startDate Дата начала тура (Y-m-d)
     * @param string $correlationId Идентификатор корреляции
     * @return int ID созданного бронирования
     *
     * @throws \Throwable При ошибке бронирования
     */
        public function bookTour(
            int $tourId,
            int $userId,
            int $personCount,
            string $startDate,
            string $correlationId,
        ): int {
            $this->fraud->check(
                userId: $userId,
                operationType: 'tour_booking',
                amount: 0,
                correlationId: $correlationId,
            );

            try {
                $bookingId = $this->db->transaction(function () use ($tourId, $userId, $personCount, $startDate, $correlationId) {
                    $tour = $this->db->table('travel_tours')->findOrFail($tourId);

                    $bookingId = $this->db->table('travel_bookings')->insertGetId([
                        'tour_id' => $tourId,
                        'user_id' => $userId,
                        'person_count' => $personCount,
                        'start_date' => $startDate,
                        'total_price' => $tour->price_per_person * $personCount,
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                        'created_at' => now(),
                    ]);

                    $this->logger->info('Tour booked', [
                        'booking_id' => $bookingId,
                        'tour_id' => $tourId,
                        'persons' => $personCount,
                        'correlation_id' => $correlationId,
                    ]);

                    return $bookingId;
                });

                return $bookingId;
            } catch (\Throwable $e) {
                $this->logger->error('Tour booking failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
