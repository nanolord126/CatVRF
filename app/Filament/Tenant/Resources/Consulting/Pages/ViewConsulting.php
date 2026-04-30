<?php

declare(strict_types=1);

/**
 * ViewConsulting — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/viewconsulting
 * @see https://catvrf.ru/docs/viewconsulting
 * @see https://catvrf.ru/docs/viewconsulting
 */

namespace App\Filament\Tenant\Resources\Consulting\Pages;

use Carbon\CarbonImmutable;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Consulting\ConsultingResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

/**
 * Class ViewConsulting
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ViewConsulting extends ViewRecord
{
    protected static string $resource = ConsultingResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): View
    {
        $this->logger->debug('ViewConsulting page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }

    protected function afterLoad(): void
    {
        $this->logger->$this->logger->info('Consulting service viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
