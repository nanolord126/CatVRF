<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Modules\VetGrooming\Application\Services\BreedCertificationService;

/**
 * CheckBreedCertificationsJob
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class CheckBreedCertificationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $daysThreshold = 30,
    ) {}

    public function handle(BreedCertificationService $certificationService, LogManager $log): void
    {
        $results = $certificationService->checkExpiringCertifications($this->daysThreshold, $this->tenantId);

        $log->info('Breed certification check completed', [
            'tenant_id' => $this->tenantId,
            'expiring' => $results['expiring'],
            'expired' => $results['expired'],
            'processed' => $results['processed'],
        ]);

        if ($results['expiring'] > 0) {
            $this->notifyAboutExpiringCertifications($this->tenantId, $results['expiring']);
        }

        if ($results['expired'] > 0) {
            $this->notifyAboutExpiredCertifications($this->tenantId, $results['expired']);
        }
    }

    private function notifyAboutExpiringCertifications(int $tenantId, int $count): void
    {
        // TODO: Implement notification logic for expiring certifications
        $log->info('Notification: Breed certifications expiring soon', [
            'tenant_id' => $tenantId,
            'count' => $count,
        ]);
    }

    private function notifyAboutExpiredCertifications(int $tenantId, int $count): void
    {
        // TODO: Implement notification logic for expired certifications
        $log->info('Notification: Breed certifications expired', [
            'tenant_id' => $tenantId,
            'count' => $count,
        ]);
    }
}
