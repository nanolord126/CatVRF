<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Providers;

use App\Domains\Delivery\Domain\Repositories\DeliveryRepositoryInterface;
use App\Domains\Delivery\Infrastructure\Persistence\Repositories\EloquentDeliveryRepository;
use Illuminate\Support\ServiceProvider;

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
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Delivery\Providers
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
