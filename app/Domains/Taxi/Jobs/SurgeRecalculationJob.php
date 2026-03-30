<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgeRecalculationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private readonly string $correlationId;

        public function __construct()
        {
            $this->correlationId = Str::uuid()->toString();
            $this->onQueue('auto');
        }

        public function handle(SurgeService $surgeService): void
        {
            Log::channel('audit')->info('Surge recalculation started', [
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

                Log::channel('audit')->info('Surge recalculation completed', [
                    'correlation_id' => $this->correlationId,
                    'zones_updated' => $activeZones->count(),
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Surge recalculation failed', [
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
