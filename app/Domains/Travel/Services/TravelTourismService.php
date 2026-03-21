<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Domains\Travel\Models\TravelTour;
use App\Domains\Travel\Models\TravelBooking;
use App\Domains\Travel\Models\TravelGuide;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TravelTourismService
{
    public function __construct(
        private readonly TravelTour $tourModel,
        private readonly TravelBooking $bookingModel,
    ) {}

    public function createTour(array $data): TravelTour
    {
        return DB::transaction(function () use ($data) {
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
        return DB::transaction(function () use ($data) {
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
        return DB::transaction(function () use ($bookingId) {
            $booking = $this->bookingModel->findOrFail($bookingId);
            $booking->update(['status' => 'completed']);
            Log::channel('audit')->info('Тур завершён', ['booking_id' => $bookingId]);
            return true;
        });
    }

    public function cancelBooking(int $bookingId, string $reason): bool
    {
        return DB::transaction(function () use ($bookingId, $reason) {
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
