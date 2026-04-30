<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Providers;

use App\Domains\Delivery\Domain\Repositories\DeliveryRepositoryInterface;
use App\Domains\Delivery\Infrastructure\Persistence\Repositories\EloquentDeliveryRepository;
use Illuminate\Support\ServiceProvider;
use App\Services\AuditService;
use App\Services\FraudControlService;

/**
 * Class DeliveryServiceProvider
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final class DeliveryServiceProvider extends ServiceProvider
{
    /**
     * Handle register operation.
     *
     * @throws \DomainException
     */
    public function register(): void
    {
        $this->app->bind(
            DeliveryRepositoryInterface::class,
            EloquentDeliveryRepository::class
        );
    }

    /**
     * Handle boot operation.
     *
     * @throws \DomainException
     */
    public function boot(): void
    {
        //
    }
}
