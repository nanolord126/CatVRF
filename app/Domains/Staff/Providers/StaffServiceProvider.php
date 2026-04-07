<?php

declare(strict_types=1);

namespace App\Domains\Staff\Providers;

use App\Domains\Staff\Application\UseCases\B2B\AddStaffMemberUseCase;
use App\Domains\Staff\Application\UseCases\B2B\UpdateStaffScheduleUseCase;
use App\Domains\Staff\Application\UseCases\B2C\GetStaffPublicProfileUseCase;
use App\Domains\Staff\Domain\Repositories\StaffMemberRepositoryInterface;
use App\Domains\Staff\Infrastructure\Persistence\Eloquent\Repositories\EloquentStaffMemberRepository;
use App\Domains\Staff\Presentation\Http\Controllers\Api\V1\StaffController;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * StaffServiceProvider — регистрирует и загружает всё, что нужно модулю Staff.
 *
 * register() — только лёгкие привязки в контейнере.
 * boot()     — маршруты, публикация ресурсов.
 */
final class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Репозиторий ────────────────────────────────────────────────────
        $this->app->bind(
            StaffMemberRepositoryInterface::class,
            EloquentStaffMemberRepository::class,
        );

        // ── UseCase B2B: AddStaffMemberUseCase ─────────────────────────────
        $this->app->when(AddStaffMemberUseCase::class)
            ->needs(LoggerInterface::class)
            ->give(fn (Application $app): LoggerInterface => $app->make('log')->channel('audit'));

        $this->app->when(AddStaffMemberUseCase::class)
            ->needs(CacheRepository::class)
            ->give(fn (Application $app): CacheRepository => $app->make('cache')->store());

        // ── UseCase B2B: UpdateStaffScheduleUseCase ────────────────────────
        $this->app->when(UpdateStaffScheduleUseCase::class)
            ->needs(LoggerInterface::class)
            ->give(fn (Application $app): LoggerInterface => $app->make('log')->channel('audit'));

        $this->app->when(UpdateStaffScheduleUseCase::class)
            ->needs(CacheRepository::class)
            ->give(fn (Application $app): CacheRepository => $app->make('cache')->store());

        // ── UseCase B2C: GetStaffPublicProfileUseCase ──────────────────────
        $this->app->when(GetStaffPublicProfileUseCase::class)
            ->needs(LoggerInterface::class)
            ->give(fn (Application $app): LoggerInterface => $app->make('log')->channel('audit'));

        $this->app->when(GetStaffPublicProfileUseCase::class)
            ->needs(CacheRepository::class)
            ->give(fn (Application $app): CacheRepository => $app->make('cache')->store());

        // ── Repository ─────────────────────────────────────────────────────
        $this->app->when(EloquentStaffMemberRepository::class)
            ->needs(LoggerInterface::class)
            ->give(fn (Application $app): LoggerInterface => $app->make('log')->channel('audit'));

        $this->app->when(EloquentStaffMemberRepository::class)
            ->needs(CacheRepository::class)
            ->give(fn (Application $app): CacheRepository => $app->make('cache')->store());

        // ── HTTP Controller ────────────────────────────────────────────────
        $this->app->when(StaffController::class)
            ->needs(LoggerInterface::class)
            ->give(fn (Application $app): LoggerInterface => $app->make('log')->channel('audit'));
    }

    public function boot(): void
    {
        $this->loadApiRoutes();
        $this->loadMigrations();
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function loadApiRoutes(): void
    {
        $routesFile = __DIR__ . '/../routes/staff-api.php';

        if (! file_exists($routesFile)) {
            return;
        }

        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/v1')
            ->group($routesFile);
    }

    private function loadMigrations(): void
    {
        $migrationsPath = __DIR__ . '/../database/migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
