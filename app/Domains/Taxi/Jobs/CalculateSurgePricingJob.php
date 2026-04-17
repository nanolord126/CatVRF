<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;

use App\Domains\Taxi\Models\TaxiTariff;
use App\Services\FraudControlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;

final readonly class CalculateSurgePricingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_TTL = 60;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $correlationId,
    ) {}

    public function handle(
        FraudControlService $fraud,
        LoggerInterface $logger,
        Cache $cache,
        DatabaseManager $db,
    ): void {
        $fraud->check(
            userId: 0,
            operationType: 'taxi_surge_calculation',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $this->correlationId,
        );

        $tariffs = TaxiTariff::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($tariffs as $tariff) {
            $surgeMultiplier = $this->calculateSurgeForTariff($tariff->id, $this->tenantId, $this->correlationId, $db);

            $tariff->update([
                'current_surge_multiplier' => $surgeMultiplier,
                'available_drivers_count' => $this->getAvailableDriversCount($tariff->vehicle_class, $this->tenantId, $db),
            ]);
        }

        $cache->forget("taxi:surge:{$this->tenantId}");

        $logger->info('Surge pricing calculated', [
            'tenant_id' => $this->tenantId,
            'tariffs_updated' => $tariffs->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function calculateSurgeForTariff(int $tariffId, int $tenantId, string $correlationId, DatabaseManager $db): float
    {
        $pendingRides = $db->table('taxi_rides')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->count();

        $availableDrivers = $db->table('taxi_drivers')
            ->where('tenant_id', $tenantId)
            ->where('is_online', true)
            ->where('status', 'active')
            ->count();

        $ratio = $availableDrivers > 0 ? $pendingRides / $availableDrivers : 2.0;

        $hour = now()->hour;
        $isRushHour = ($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 19);

        $surgeMultiplier = match(true) {
            $ratio >= 2.0 => 2.5,
            $ratio >= 1.5 => 2.0,
            $ratio >= 1.2 => 1.5,
            $ratio >= 1.0 => 1.2,
            default => 1.0,
        };

        if ($isRushHour) {
            $surgeMultiplier = min($surgeMultiplier * 1.2, 3.0);
        }

        return $surgeMultiplier;
    }

    private function getAvailableDriversCount(string $vehicleClass, int $tenantId, DatabaseManager $db): int
    {
        return $db->table('taxi_drivers')
            ->join('taxi_vehicles', 'taxi_drivers.id', '=', 'taxi_vehicles.driver_id')
            ->where('taxi_drivers.tenant_id', $tenantId)
            ->where('taxi_drivers.is_online', true)
            ->where('taxi_drivers.status', 'active')
            ->where('taxi_vehicles.vehicle_class', $vehicleClass)
            ->where('taxi_vehicles.is_active', true)
            ->count();
    }
}
