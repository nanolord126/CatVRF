<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

final class UpdateCustomsStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public int $backoff = [30, 60, 120, 300, 600];

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

        if ($import === null || $import->status !== 'customs_processing') {
            return;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Correlation-ID' => $this->correlationId,
                ])
                ->post(config('services.customs.api_url') . '/check-status', [
                    'vin' => $import->vin,
                    'import_id' => $import->id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $customsStatus = $data['status'] ?? 'pending';

                $statusMapping = [
                    'pending' => 'customs_processing',
                    'approved' => 'transportation',
                    'rejected' => 'customs_rejected',
                    'inspection_required' => 'customs_inspection',
                ];

                $newStatus = $statusMapping[$customsStatus] ?? 'customs_processing';

                DB::table('car_imports')
                    ->where('id', $this->importId)
                    ->update([
                        'status' => $newStatus,
                        'metadata' => array_merge(
                            json_decode($import->metadata ?? '{}', true),
                            [
                                'customs_api_status' => $customsStatus,
                                'customs_api_response' => $data,
                                'customs_status_updated_at' => now()->toIso8601String(),
                            ]
                        ),
                        'updated_at' => now(),
                    ]);

                Log::channel('audit')->info('car.import.customs.status.updated', [
                    'import_id' => $this->importId,
                    'correlation_id' => $this->correlationId,
                    'customs_status' => $customsStatus,
                    'new_status' => $newStatus,
                ]);

                if ($newStatus === 'transportation') {
                    $this->dispatchTransportationJob();
                }
            }
        } catch (\Throwable $e) {
            Log::channel('audit')->error('car.import.customs.status.error', [
                'import_id' => $this->importId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function dispatchTransportationJob(): void
    {
        UpdateLogisticsTrackingJob::dispatch(
            importId: $this->importId,
            correlationId: $this->correlationId,
        )->delay(now()->addMinutes(30));
    }
}
