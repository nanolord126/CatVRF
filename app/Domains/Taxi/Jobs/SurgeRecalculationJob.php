<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

final class SurgeRecalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly string $correlationId;

        public function __construct(private readonly LoggerInterface $logger)
        {
            $this->correlationId = Str::uuid()->toString();
            $this->onQueue('auto');
        }

        public function handle(SurgeService $surgeService): void
        {
            $this->logger->$this->logger->info('Surge recalculation started', [
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
                        'last_calculated_at' => CarbonImmutable::now(),
                    ]);
                }

                $this->logger->$this->logger->info('Surge recalculation completed', [
                    'correlation_id' => $this->correlationId,
                    'zones_updated' => $activeZones->count(),
                ]);
            } catch (Exception $e) {
                $this->logger->error('Surge recalculation failed', [
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


    public function failed(Exception $exception): void
    {
        $this->logger->error('taxi job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}

