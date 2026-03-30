<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelTourismService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
            private readonly TravelTour $tourModel,
            private readonly TravelBooking $bookingModel,
        ) {}

        public function createTour(array $data): TravelTour
        {


            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($data) {
                $tour = $this->tourModel->create($data);
                Log::channel('audit')->info('Тур создан', [
                    'tour_id' => $tour->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);
                return $tour;
            });
        }

        public function bookTour(array $data): TravelBooking
        {


            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($data) {
                $booking = $this->bookingModel->create($data);
                Log::channel('audit')->info('Тур забронирован', [
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


            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($bookingId) {
                $booking = $this->bookingModel->findOrFail($bookingId);
                $booking->update(['status' => 'completed']);
                Log::channel('audit')->info('Тур завершён', ['booking_id' => $bookingId]);
                return true;
            });
        }

        public function cancelBooking(int $bookingId, string $reason): bool
        {


            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($bookingId, $reason) {
                $booking = $this->bookingModel->findOrFail($bookingId);
                $booking->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
                Log::channel('audit')->warning('Бронь отменена', [
                    'booking_id' => $bookingId,
                    'reason' => $reason,
                ]);
                return true;
            });
        }
}
