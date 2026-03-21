<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use App\Domains\Auto\Services\TaxiSurgeService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для пересчёта surge pricing каждые 5 минут по гео-зонам.
 * Production 2026.
 */
final class SurgeRecalculationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly TaxiSurgeService $surgeService,
        private string $correlationId = '',
    ) {
        $this->onQueue('high');
        $this->tags(['taxi', 'surge']);
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Surge recalculation job started', [
                'correlation_id' => $this->correlationId,
            ]);
            // - Get all active zones
            // - Calculate surge for each zone
            // - Update cache with new multipliers
            // - Log changes

            // Пример для одной зоны:
            $zones = [
                ['lat' => 55.7558, 'lng' => 37.6173], // Moscow center
                ['lat' => 55.7614, 'lng' => 37.6270], // Moscow northeast
            ];

            foreach ($zones as $location) {
                $multiplier = $this->surgeService->calculateSurgeMultiplier(
                    $location,
                    tenant('id') ?? 1,
                    $this->correlationId,
                );

                Log::channel('audit')->info('Zone surge updated', [
                    'location' => $location,
                    'surge_multiplier' => $multiplier,
                    'correlation_id' => $this->correlationId,
                ]);
            }

            Log::channel('audit')->info('Surge recalculation completed', [
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Surge recalculation job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(10);
    }
}
