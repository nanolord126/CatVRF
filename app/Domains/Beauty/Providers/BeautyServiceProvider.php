<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Providers;

use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\ConsumableRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\MasterRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\ServiceRepositoryInterface;
use App\Domains\Beauty\Domain\Services\ConsumableDeductionServiceInterface;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories\EloquentAppointmentRepository;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories\EloquentConsumableRepository;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories\EloquentMasterRepository;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalonRepository;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceRepository;
use App\Domains\Beauty\Infrastructure\Services\ConsumableDeductionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Class BeautyServiceProvider
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Beauty\Providers
 */
final class BeautyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalonRepositoryInterface::class, EloquentSalonRepository::class);
        $this->app->bind(MasterRepositoryInterface::class, EloquentMasterRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
        $this->app->bind(AppointmentRepositoryInterface::class, EloquentAppointmentRepository::class);
        $this->app->bind(ConsumableRepositoryInterface::class, EloquentConsumableRepository::class);
        $this->app->bind(ConsumableDeductionServiceInterface::class, ConsumableDeductionService::class);
    }

    public function boot(): void
    {
        $this->app->register(BeautyEventServiceProvider::class);
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api/b2c/beauty')
            ->middleware('api')
            ->group(base_path('app/Domains/Beauty/Routes/b2c.php'));

        Route::prefix('api/b2b/beauty')
            ->middleware(['api', 'auth:sanctum', 'tenant'])
            ->group(base_path('app/Domains/Beauty/Routes/b2b.php'));
    }
}
