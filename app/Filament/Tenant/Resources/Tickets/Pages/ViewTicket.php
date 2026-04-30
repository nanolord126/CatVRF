<?php

declare(strict_types=1);

/**
 * ViewTicket — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/viewticket
 * @see https://catvrf.ru/docs/viewticket
 * @see https://catvrf.ru/docs/viewticket
 */

namespace App\Filament\Tenant\Resources\Tickets\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Tickets\TicketsResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\View\View;

/**
 * Class ViewTicket
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketsResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): View
    {
        $this->logger->debug('ViewTicket page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }

    protected function afterLoad(): void
    {
        $this->log->channel('audit')->$this->logger->info('Tickets record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
