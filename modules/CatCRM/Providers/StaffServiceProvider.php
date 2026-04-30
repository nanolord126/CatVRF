<?php

declare(strict_types=1);

namespace Modules\CatCRM\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CatCRM\Domain\Staff\Repositories\EmployeeRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\SkillRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\BadgeRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\AchievementRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\MentorshipRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\PeerReviewRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\ShiftRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\LeaveRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\WellnessMetricsRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\TrainingCourseRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\CertificationRepositoryInterface;
use Modules\CatCRM\Infrastructure\Repositories\EloquentEmployeeRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentSkillRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentBadgeRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentAchievementRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentMentorshipRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentPeerReviewRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentShiftRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentLeaveRepository;
use Modules\CatCRM\Infrastructure\Repositories\EloquentWellnessMetricsRepository;

/**
 * StaffServiceProvider — Layer 8: Infrastructure/External Services Layer (Service Binding)
 *
 * Binds repository interfaces to implementations
 * Part of 9-layer architecture
 */
final class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Layer 6 -> Layer 7 bindings (Repository Interfaces to Implementations)
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(SkillRepositoryInterface::class, EloquentSkillRepository::class);
        $this->app->bind(BadgeRepositoryInterface::class, EloquentBadgeRepository::class);
        $this->app->bind(AchievementRepositoryInterface::class, EloquentAchievementRepository::class);
        $this->app->bind(MentorshipRepositoryInterface::class, EloquentMentorshipRepository::class);
        $this->app->bind(PeerReviewRepositoryInterface::class, EloquentPeerReviewRepository::class);
        $this->app->bind(ShiftRepositoryInterface::class, EloquentShiftRepository::class);
        $this->app->bind(LeaveRepositoryInterface::class, EloquentLeaveRepository::class);
        $this->app->bind(WellnessMetricsRepositoryInterface::class, EloquentWellnessMetricsRepository::class);
    }

    public function boot(): void
    {
        // Load routes for Layer 2 (API/HTTP)
        $this->loadRoutesFrom(base_path('routes/api/staff.php'));
    }
}
