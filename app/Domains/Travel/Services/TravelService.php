declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Travel\Models\TravelTour;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final /**
 * TravelService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TravelService
{
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
$this->db->transaction(function () use ($tourId, $seats) {
            $tour = TravelTour::lockForUpdate()->find($tourId);

            if (!$tour || ($tour->booked + $seats) > $tour->capacity) {
                throw new \Exception('Tour is fully booked');
            }

            $tour->update(['booked' => $tour->booked + $seats]);

            $this->log->channel('audit')->info('Tour booked', [
                'correlation_id' => $this->correlationId,
                'tour_id' => $tourId,
                'seats' => $seats,
            ]);

            return ['success' => true, 'tour' => $tour];
        });
    }
}
