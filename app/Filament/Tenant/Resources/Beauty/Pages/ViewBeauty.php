<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautyResource;
use Filament\Resources\Pages\ViewRecord;
use Psr\Log\LoggerInterface;

/**
 * ViewBeauty — Filament Page (Layer 9).
 *
 * Tenant-scoped salon view with audit logging.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\Pages
 */
final class ViewBeauty extends ViewRecord
{
    protected static string $resource = BeautyResource::class;

    protected function afterLoad(): void
    {
        app(LoggerInterface::class)->info('Beauty salon viewed', [
            'record_id'      => $this->record->id,
            'uuid'           => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id'        => filament()->auth()->id(),
            'tenant_id'      => filament()->getTenant()?->id,
            'timestamp'      => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        app(LoggerInterface::class)->debug('ViewBeauty page rendered', [
            'record_id' => $this->record->id,
            'user_id'   => filament()->auth()->id(),
        ]);

        return parent::render();
    }
}
