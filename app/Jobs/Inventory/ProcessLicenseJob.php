<?php

declare(strict_types=1);

namespace App\Jobs\Inventory;

use App\Services\Compliance\LicenseManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessLicenseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly string $action,
        private readonly array $data,
        private readonly string $correlationId,
    ) {
        $this->onQueue('compliance');
    }

    public function handle(LicenseManagementService $licenseService): void
    {
        Log::info('Processing license job', [
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
        ]);

        match ($this->action) {
            'register_license' => $licenseService->registerLicense(
                warehouseId: $this->data['warehouse_id'],
                tenantId: $this->data['tenant_id'],
                licenseType: $this->data['license_type'],
                licenseNumber: $this->data['license_number'],
                issuedDate: $this->data['issued_date'],
                expiryDate: $this->data['expiry_date'],
                issuedBy: $this->data['issued_by'],
                userId: $this->data['user_id'],
                correlationId: $this->correlationId,
            ),
            'register_storage_zone' => $licenseService->registerStorageZone(
                warehouseId: $this->data['warehouse_id'],
                tenantId: $this->data['tenant_id'],
                category: $this->data['category'],
                minTemp: $this->data['min_temperature'],
                maxTemp: $this->data['max_temperature'],
                userId: $this->data['user_id'],
            ),
            'revoke_license' => $licenseService->revokeLicense(
                licenseId: $this->data['license_id'],
                reason: $this->data['reason'],
                userId: $this->data['user_id'],
            ),
            default => throw new \InvalidArgumentException("Unknown action: {$this->action}"),
        };
    }

    public function failed(Throwable $exception): void
    {
        Log::error('License job failed', [
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
