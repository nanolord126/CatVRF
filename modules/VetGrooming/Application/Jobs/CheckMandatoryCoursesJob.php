<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Modules\VetGrooming\Application\Services\ProfessionalDevelopmentService;
use Modules\VetGrooming\Domain\Repositories\ProfessionalDevelopmentPlanRepositoryInterface;

/**
 * CheckMandatoryCoursesJob
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class CheckMandatoryCoursesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
    ) {}

    public function handle(
        ProfessionalDevelopmentService $developmentService,
        ProfessionalDevelopmentPlanRepositoryInterface $planRepository,
        LogManager $log,
    ): void {
        $plans = $planRepository->findByTenantId($this->tenantId);

        foreach ($plans as $plan) {
            try {
                $compliance = $developmentService->checkMandatoryCoursesCompliance(
                    $plan->masterId,
                    $plan->professionType,
                    $this->tenantId,
                );

                if (! $compliance['compliant']) {
                    $this->logNonCompliance($plan, $compliance);
                    $this->notifyAboutNonCompliance($plan, $compliance);
                }

                // Update development score
                $developmentService->calculateDevelopmentScore($plan->masterId, $this->tenantId);
            } catch (\Exception $e) {
                $log->error('Failed to check mandatory courses for plan', [
                    'plan_id' => $plan->id,
                    'master_id' => $plan->masterId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $log->info('Mandatory courses check completed', [
            'tenant_id' => $this->tenantId,
            'plans_checked' => count($plans),
        ]);
    }

    private function logNonCompliance($plan, array $compliance): void
    {
        $log->warning('Master non-compliant with mandatory courses', [
            'plan_id' => $plan->id,
            'master_id' => $plan->masterId,
            'profession_type' => $plan->professionType,
            'missing_courses' => count($compliance['missing_courses']),
            'expired_courses' => count($compliance['expired']),
            'expiring_soon' => count($compliance['expiring_soon']),
        ]);
    }

    private function notifyAboutNonCompliance($plan, array $compliance): void
    {
        // TODO: Implement notification logic
        // This could send emails, in-app notifications, or integrate with notification system
        // For now, we'll log it
        $log->info('Notification: Master has mandatory course compliance issues', [
            'master_id' => $plan->masterId,
            'missing_courses_count' => count($compliance['missing_courses']),
            'expired_count' => count($compliance['expired']),
            'expiring_soon_count' => count($compliance['expiring_soon']),
        ]);
    }
}
