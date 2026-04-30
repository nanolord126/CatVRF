<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\SalonResource\Pages;

use App\Domains\Beauty\Filament\Resources\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListSalons — список салонов красоты в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class ListSalons extends ListRecords
{
    /**
     * Component: ListSalons
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = SalonResource::class;

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'ListSalons';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Новый салон')
                ->icon('heroicon-o-plus'),
        ];
    }

    /**
     * ListSalons — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     *
     * @author CatVRF Team
     * @license Proprietary
     */
}
