<?php

declare(strict_types=1);

/**
 * ViewBook — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/viewbook
 * @see https://catvrf.ru/docs/viewbook
 * @see https://catvrf.ru/docs/viewbook
 */

namespace App\Filament\Tenant\Resources\Books\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Books\BooksResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\View\View;

/**
 * Class ViewBook
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ViewBook extends ViewRecord
{
    protected static string $resource = BooksResource::class;

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function render(): View
    {
        $this->log->channel('audit')->debug('ViewBook page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }

    protected function afterLoad(): void
    {
        $this->log->channel('audit')->$this->logger->info('Books record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
