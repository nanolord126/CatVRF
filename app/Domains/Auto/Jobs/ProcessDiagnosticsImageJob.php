<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use App\Domains\Auto\Services\AIDiagnosticsService;
use App\Domains\Auto\DTOs\AIDiagnosticsDto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

final class ProcessDiagnosticsImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly string $vin,
        private readonly string $imagePath,
        private readonly ?float $latitude,
        private readonly ?float $longitude,
        private readonly string $correlationId,
        private readonly bool $isB2b,
    ) {}

    public function handle(AIDiagnosticsService $diagnosticsService): void
    {
        $imageFullPath = Storage::disk('local')->path($this->imagePath);

        if (!file_exists($imageFullPath)) {
            Log::channel('audit')->error('auto.diagnostics.image_not_found', [
                'correlation_id' => $this->correlationId,
                'image_path' => $this->imagePath,
            ]);

            return;
        }

        $uploadedFile = new UploadedFile(
            $imageFullPath,
            basename($imageFullPath),
            'image/jpeg',
            null,
            true,
        );

        $dto = new AIDiagnosticsDto(
            tenantId: $this->tenantId,
            userId: $this->userId,
            vin: $this->vin,
            photo: $uploadedFile,
            latitude: $this->latitude,
            longitude: $this->longitude,
            correlationId: $this->correlationId,
            ipAddress: null,
            deviceFingerprint: null,
            isB2b: $this->isB2b,
        );

        try {
            $result = $diagnosticsService->diagnoseByPhotoAndVIN($dto);

            Log::channel('audit')->info('auto.diagnostics.job.completed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $this->userId,
                'vehicle_id' => $result['vehicle']['id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('auto.diagnostics.job.failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            if (file_exists($imageFullPath)) {
                Storage::disk('local')->delete($this->imagePath);
            }
        }
    }
}
