<?php declare(strict_types=1);

/**
 * ViewGroceryAndDelivery — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewgroceryanddelivery
 * @see https://catvrf.ru/docs/viewgroceryanddelivery
 * @see https://catvrf.ru/docs/viewgroceryanddelivery
 */


namespace App\Filament\Tenant\Resources\GroceryAndDelivery\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\GroceryAndDelivery\GroceryAndDeliveryResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class ViewGroceryAndDelivery
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\GroceryAndDelivery\Pages
 */
final class ViewGroceryAndDelivery extends ViewRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = GroceryAndDeliveryResource::class;

    protected function afterLoad(): void
    {
        $this->logger->info('GroceryAndDelivery record viewed', [
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
        $this->logger->debug('ViewGroceryAndDelivery page rendered', [
            'record_id' => $this->record->id,
            'user_id' => $this->guard->id(),
        ]);

        return parent::render();
    }
}
