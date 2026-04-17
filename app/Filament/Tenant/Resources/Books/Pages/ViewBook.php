<?php declare(strict_types=1);

/**
 * ViewBook — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewbook
 * @see https://catvrf.ru/docs/viewbook
 * @see https://catvrf.ru/docs/viewbook
 */


namespace App\Filament\Tenant\Resources\Books\Pages;



use App\Filament\Tenant\Resources\Books\BooksResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class ViewBook
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Books\Pages
 */
final class ViewBook extends ViewRecord
{
    protected static string $resource = BooksResource::class;

    protected function afterLoad(): void
    {
        Log::channel('audit')->info('Books record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        Log::channel('audit')->debug('ViewBook page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }
}
