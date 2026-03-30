<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
            private readonly string $correlationId = '',
        ) {
            $this->correlationId = $correlationId ?: Str::uuid()->toString();
        }

        public function bookTour(int $tourId, int $seats): array
        {


            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($tourId, $seats) {
                $tour = TravelTour::lockForUpdate()->find($tourId);

                if (!$tour || ($tour->booked + $seats) > $tour->capacity) {
                    throw new \Exception('Tour is fully booked');
                }

                $tour->update(['booked' => $tour->booked + $seats]);

                Log::channel('audit')->info('Tour booked', [
                    'correlation_id' => $this->correlationId,
                    'tour_id' => $tourId,
                    'seats' => $seats,
                ]);

                return ['success' => true, 'tour' => $tour];
            });
        }
}
