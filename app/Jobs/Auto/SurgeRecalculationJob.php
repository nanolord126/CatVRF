<?php declare(strict_types=1);

namespace App\Jobs\Auto;

use App\Domains\Auto\Services\SurgePricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SurgeRecalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('high');
    }

    public function tags(): array
    {
        return ['surge', 'pricing', 'auto'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    public function handle(SurgePricingService $surgePricingService): void
    {
        try {
            $this->db->transaction(function () use ($surgePricingService) {
                $zones = $surgePricingService->getActiveSurgeZones();

                foreach ($zones as $zone) {
                    $newMultiplier = $surgePricingService->calculateSurgeMultiplier(
                        $zone->id,
                        $this->correlationId
                    );

                    if ($newMultiplier !== $zone->surge_multiplier) {
                        $surgePricingService->updateSurgeMultiplier(
                            $zone->id,
                            $newMultiplier,
                            $this->correlationId
                        );

                        $this->log->channel('audit')->info('Surge multiplier updated', [
                            'correlation_id' => $this->correlationId,
                            'zone_id' => $zone->id,
                            'old_multiplier' => $zone->surge_multiplier,
                            'new_multiplier' => $newMultiplier,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Surge recalculation failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
