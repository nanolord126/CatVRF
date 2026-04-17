<?php declare(strict_types=1);

namespace App\Domains\Education\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Domains\Education\Policies\LearningPathPolicy;
use App\Domains\Education\Policies\VerticalCoursePolicy;
use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\VerticalCourse;

final class EducationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Enrollment::class, LearningPathPolicy::class);
        Gate::policy(VerticalCourse::class, VerticalCoursePolicy::class);
    }
}
