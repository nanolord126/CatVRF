<?php declare(strict_types=1);

namespace App\Jobs;

use App\Services\Tenancy\TenantOnboardingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Cleanup Job
 * 
 * Scheduled job to permanently delete tenant data
 * after retention period expires (GDPR/152-ФЗ compliance)
 */
final class TenantCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    public function __construct(
        public readonly string $tenantId,
    ) {}

    public function handle(TenantOnboardingService $onboardingService): void
    {
        try {
            $deleted = $onboardingService->permanentlyDeleteTenant($this->tenantId);

            if ($deleted) {
                Log::channel('tenant')->info('Tenant data permanently deleted', [
                    'tenant_id' => $this->tenantId,
                ]);
            } else {
                Log::channel('tenant')->warning('Tenant not found for cleanup', [
                    'tenant_id' => $this->tenantId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('tenant')->error('Tenant cleanup failed', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('tenant')->error('Tenant cleanup job failed', [
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}
