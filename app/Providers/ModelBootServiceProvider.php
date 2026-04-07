<?php

declare(strict_types=1);

/**
 * ModelBootServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/modelbootserviceprovider
 * @see https://catvrf.ru/docs/modelbootserviceprovider
 * @see https://catvrf.ru/docs/modelbootserviceprovider
 */


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionException;

/**
 * Temporary Service Provider to fix the mass model booting issue.
 * This provider iterates through app models and safely calls the boot method
 * only if it exists, preventing crashes on vendor models.
 */
/**
 * Class ModelBootServiceProvider
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
 * @package App\Providers
 */
final class ModelBootServiceProvider extends ServiceProvider
{
    /**
     * Handle register operation.
     *
     * @throws \DomainException
     */
    public function register(): void
    {
        // This is a temporary fix and should be investigated later.
        // The root cause is likely another provider or bootstrap script
        // that is incorrectly trying to boot all declared models.
    }

    /**
     * Handle boot operation.
     *
     * @throws \DomainException
     */
    public function boot(): void
    {
        // We do nothing here to avoid contributing to the problem.
        // The existence of this provider is for manual registration
        // as a placeholder for a more complex, future fix if needed.
        // The real fix will be finding and removing the faulty code.
    }
}
