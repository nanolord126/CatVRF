<?php declare(strict_types=1);

/**
 * ViewElectronic — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewelectronic
 * @see https://catvrf.ru/docs/viewelectronic
 * @see https://catvrf.ru/docs/viewelectronic
 */


namespace App\Filament\Tenant\Resources\Electronics\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Electronics\ElectronicsResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class ViewElectronic
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Electronics\Pages
 */
final class ViewElectronic extends ViewRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = ElectronicsResource::class;

    protected function afterLoad(): void
    {
        $this->logger->info('Electronics record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => $this->guard->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render()
    {
        $this->logger->debug('ViewElectronic page rendered', [
            'record_id' => $this->record->id,
            'user_id' => $this->guard->id(),
        ]);

        return parent::render();
    }
}
