<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Services\Tenancy\TenantOnboardingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class TenantCleanupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        public readonly string $tenantId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('tenant-cleanup');
    }

    public function tags(): array
    {
        return ['tenant', 'cleanup', 'tenant:' . $this->tenantId];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(3);
    }

    public function handle(TenantOnboardingService $onboardingService): void
    {
        $this->logger->channel('audit')->$this->logger->info('[TenantCleanupJob] Started', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $deleted = $onboardingService->permanentlyDeleteTenant($this->tenantId);

            if ($deleted) {
                $this->db->table('tenant_deletion_logs')->insert([
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                    'deleted_at' => CarbonImmutable::now(),
                ]);

                $this->logger->channel('audit')->$this->logger->info('[TenantCleanupJob] Tenant deleted', [
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                $this->logger->channel('audit')->warning('[TenantCleanupJob] Tenant not found', [
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[TenantCleanupJob] Failed', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[TenantCleanupJob] Failed permanently', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
