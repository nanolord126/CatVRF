<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;

use App\Domains\Taxi\Models\TaxiSurgeZone;
use App\Domains\Taxi\Services\SurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SurgeRecalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('auto');
    }

    public function handle(SurgeService $surgeService): void
    {
        $this->log->channel('audit')->info('Surge recalculation started', [
            'correlation_id' => $this->correlationId,
            'job' => self::class,
        ]);

        try {
            $activeZones = TaxiSurgeZone::where('is_active', true)->get();

            foreach ($activeZones as $zone) {
                $multiplier = $surgeService->calculateSurgeMultiplier(
                    $zone->id,
                    $zone->tenant_id
                );

                $zone->update([
                    'current_multiplier' => $multiplier,
                    'last_calculated_at' => now(),
                ]);
            }

            $this->log->channel('audit')->info('Surge recalculation completed', [
                'correlation_id' => $this->correlationId,
                'zones_updated' => $activeZones->count(),
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Surge recalculation failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['auto', 'surge', 'recalculation', $this->correlationId];
    }
}
