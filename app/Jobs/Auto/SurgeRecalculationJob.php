<?php declare(strict_types=1);

namespace App\Jobs\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgeRecalculationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                DB::transaction(function () use ($surgePricingService) {
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

                            Log::channel('audit')->info('Surge multiplier updated', [
                                'correlation_id' => $this->correlationId,
                                'zone_id' => $zone->id,
                                'old_multiplier' => $zone->surge_multiplier,
                                'new_multiplier' => $newMultiplier,
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Surge recalculation failed', [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
