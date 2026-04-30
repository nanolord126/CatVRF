<?php

declare(strict_types=1);

namespace App\Jobs\Auto;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

final class SurgeRecalculationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('emergency');
    }

    public function tags(): array
    {
        return ['surge', 'pricing', 'auto'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addMinutes(5);
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

                        $this->logger->channel('audit')->$this->logger->info('Surge multiplier updated', [
                            'correlation_id' => $this->correlationId,
                            'zone_id' => $zone->id,
                            'old_multiplier' => $zone->surge_multiplier,
                            'new_multiplier' => $newMultiplier,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('Surge recalculation failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
