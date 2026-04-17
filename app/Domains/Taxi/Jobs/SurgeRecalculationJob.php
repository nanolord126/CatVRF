<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class SurgeRecalculationJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        private readonly string $correlationId;

        public function __construct(private readonly LoggerInterface $logger)
        {
            $this->correlationId = Str::uuid()->toString();
            $this->onQueue('auto');
        }

        public function handle(SurgeService $surgeService): void
        {
            $this->logger->info('Surge recalculation started', [
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

                $this->logger->info('Surge recalculation completed', [
                    'correlation_id' => $this->correlationId,
                    'zones_updated' => $activeZones->count(),
                ]);
            } catch (\Throwable $e) {
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
}

