<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class TravelTourismService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly TravelTour $tourModel,
            private readonly TravelBooking $bookingModel,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createTour(array $data): TravelTour
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($data) {
                $tour = $this->tourModel->create($data);
                $this->logger->info('Тур создан', [
                    'tour_id' => $tour->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);
                return $tour;
            });
        }

        public function bookTour(array $data): TravelBooking
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($data) {
                $booking = $this->bookingModel->create($data);
                $this->logger->info('Тур забронирован', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);
                return $booking;
            });
        }

        public function getAvailableTours(string $destination, string $dateFrom, string $dateTo): Collection
        {

            return $this->tourModel
                ->where('destination', $destination)
                ->whereDate('start_date', '>=', $dateFrom)
                ->whereDate('end_date', '<=', $dateTo)
                ->where('available_slots', '>', 0)
                ->get();
        }

        public function completeTour(int $bookingId): bool
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($bookingId) {
                $booking = $this->bookingModel->findOrFail($bookingId);
                $booking->update(['status' => 'completed']);
                $this->logger->info('Тур завершён', ['booking_id' => $bookingId]);
                return true;
            });
        }

        public function cancelBooking(int $bookingId, string $reason): bool
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($bookingId, $reason) {
                $booking = $this->bookingModel->findOrFail($bookingId);
                $booking->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
                $this->logger->warning('Бронь отменена', [
                    'booking_id' => $bookingId,
                    'reason' => $reason,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return true;
            });
        }
}
