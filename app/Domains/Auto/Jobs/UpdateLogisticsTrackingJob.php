<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

final class UpdateLogisticsTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $importId,
        public readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $import = DB::table('car_imports')
            ->where('id', $this->importId)
            ->lockForUpdate()
            ->first();

        if ($import === null || !in_array($import->status, ['transportation', 'delivery'], true)) {
            return;
        }

        try {
            $cacheKey = "logistics:tracking:$this->importId";
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return;
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'X-Correlation-ID' => $this->correlationId,
                ])
                ->get(config('services.logistics.api_url') . '/tracking/' . $import->id);

            if ($response->successful()) {
                $data = $response->json();
                $trackingStatus = $data['status'] ?? 'in_transit';
                $currentLocation = $data['location'] ?? null;
                $eta = $data['eta'] ?? null;

                $statusMapping = [
                    'in_transit' => 'transportation',
                    'at_customs' => 'customs_clearance',
                    'delivered' => 'delivery',
                    'completed' => 'completed',
                ];

                $newStatus = $statusMapping[$trackingStatus] ?? $import->status;

                $updateData = [
                    'status' => $newStatus,
                    'metadata' => array_merge(
                        json_decode($import->metadata ?? '{}', true),
                        [
                            'tracking_status' => $trackingStatus,
                            'current_location' => $currentLocation,
                            'estimated_arrival' => $eta,
                            'tracking_updated_at' => now()->toIso8601String(),
                        ]
                    ),
                    'updated_at' => now(),
                ];

                DB::table('car_imports')
                    ->where('id', $this->importId)
                    ->update($updateData);

                Cache::put($cacheKey, true, 300);

                Log::channel('audit')->info('car.import.logistics.tracking.updated', [
                    'import_id' => $this->importId,
                    'correlation_id' => $this->correlationId,
                    'tracking_status' => $trackingStatus,
                    'new_status' => $newStatus,
                    'location' => $currentLocation,
                ]);

                if ($newStatus !== 'completed') {
                    $this->dispatchNextUpdate();
                }
            }
        } catch (\Throwable $e) {
            Log::channel('audit')->error('car.import.logistics.tracking.error', [
                'import_id' => $this->importId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function dispatchNextUpdate(): void
    {
        self::dispatch(
            importId: $this->importId,
            correlationId: $this->correlationId,
        )->delay(now()->addHours(2));
    }
}
