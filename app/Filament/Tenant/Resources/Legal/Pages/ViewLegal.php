<?php

declare(strict_types=1);

/**
 * ViewLegal — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/viewlegal
 * @see https://catvrf.ru/docs/viewlegal
 * @see https://catvrf.ru/docs/viewlegal
 */

namespace App\Filament\Tenant\Resources\Legal\Pages;

use Carbon\CarbonImmutable;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Legal\LegalResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

/**
 * Class ViewLegal
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ViewLegal extends ViewRecord
{
    protected static string $resource = LegalResource::class;

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
        $this->logger->debug('ViewLegal page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }

    protected function afterLoad(): void
    {
        $this->logger->$this->logger->info('Legal record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
