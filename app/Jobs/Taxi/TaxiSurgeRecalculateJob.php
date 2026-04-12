<?php

declare(strict_types=1);

namespace App\Jobs\Taxi;


use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;

/**
 * Пересчитывает surge-коэффициенты каждые 5 минут.
 * Запускается через scheduler: $schedule->job(TaxiSurgeRecalculateJob::class)->everyFiveMinutes();
 */
final class TaxiSurgeRecalculateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private string $correlationId = '',
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
    )
    {
        // Implementation required by canon
    }

    public function handle(): void
    {
        $correlationId = $this->correlationId ?: Str::uuid()->toString();

        $this->logger->channel('audit')->info('TaxiSurgeRecalculateJob started', [
            'correlation_id' => $correlationId,
        ]);

        $zones = $this->db->table('taxi_surge_zones')->where('is_active', true)->get();

        foreach ($zones as $zone) {
            // Считаем количество заказов в зоне за последние 5 минут
            $demandCount = $this->db->table('taxi_rides')
                ->where('status', 'requested')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->whereRaw('ST_Contains(
                    (SELECT polygon FROM taxi_surge_zones WHERE id = ?),
                    start_point
                )', [$zone->id])
                ->count();

            $newMultiplier = match (true) {
                $demandCount >= 50 => 2.5,
                $demandCount >= 30 => 2.0,
                $demandCount >= 15 => 1.5,
                $demandCount >= 5  => 1.2,
                default            => 1.0,
            };

            $this->db->table('taxi_surge_zones')
                ->where('id', $zone->id)
                ->update(['surge_multiplier' => $newMultiplier]);

            // Инвалидируем кэш для данной зоны
            $this->cache->forget("taxi:surge:zone:{$zone->id}");
        }

        $this->logger->channel('audit')->info('TaxiSurgeRecalculateJob finished', [
            'correlation_id' => $correlationId,
            'zones_processed' => $zones->count(),
        ]);
    }

    public function tags(): array
    {
        return ['taxi', 'surge', 'recalculate'];
    }
}
