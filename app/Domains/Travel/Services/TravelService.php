<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Travel\Models\TravelTour;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class TravelService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookTour(int $tourId, int $seats): array
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'bookTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL bookTour', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($tourId, $seats) {
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
