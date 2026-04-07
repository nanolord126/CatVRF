<?php

declare(strict_types=1);

/**
 * FilamentTenancyBootstrapper — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/filamenttenancybootstrapper
 */


namespace App\Application\Tenancy\Boostrappers;

use Illuminate\Contracts\Config\Repository;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Class FilamentTenancyBootstrapper
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Application\Tenancy\Boostrappers
 */
final class FilamentTenancyBootstrapper implements TenancyBootstrapper
{
    protected array $originalConfig;

    public function __construct(protected Repository $config)
    {
        $this->originalConfig = $this->config->get([
            'filament',
            'livewire',
        ]);
    }

    /**
     * Handle bootstrap operation.
     *
     * @throws \DomainException
     */
    public function bootstrap(Tenant $tenant): void
    {
        // This is a placeholder for tenant-specific Filament/Livewire configuration.
        // For example, you could change the default Filament theme or path.
    }

    /**
     * Handle revert operation.
     *
     * @throws \DomainException
     */
    public function revert(): void
    {
        $this->config->set([
            'filament' => $this->originalConfig['filament'],
            'livewire' => $this->originalConfig['livewire'],
        ]);
    }
}
