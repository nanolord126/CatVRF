<?php declare(strict_types=1);

namespace App\Domains\Taxi\Jobs;

use App\Domains\Taxi\Models\TaxiDriver;
use App\Services\FraudControlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;

final readonly class UpdateDriverLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_TTL = 60;

    public function __construct(
        public readonly int $driverId,
        public readonly float $lat,
        public readonly float $lon,
        public readonly string $correlationId,
    ) {}

    public function handle(
        FraudControlService $fraud,
        LoggerInterface $logger,
        Cache $cache,
    ): void {
        $fraud->check(
            userId: $this->driverId,
            operationType: 'taxi_driver_location_update',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $this->correlationId,
        );

        TaxiDriver::where('id', $this->driverId)->update([
            'current_lat' => $this->lat,
            'current_lon' => $this->lon,
            'location_updated_at' => now(),
            'last_active_at' => now(),
        ]);

        $cache->put("taxi:driver:location:{$this->driverId}", [
            'lat' => $this->lat,
            'lon' => $this->lon,
            'updated_at' => now()->toIso8601String(),
        ], self::CACHE_TTL);

        $logger->info('Driver location updated', [
            'driver_id' => $this->driverId,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
