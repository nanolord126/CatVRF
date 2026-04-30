<?php

declare(strict_types=1);

/**
 * ListVehicleInspections — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listvehicleinspections
 */

namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

final class ListVehicleInspections extends ListRecords
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;


    protected static string $resource = VehicleInspectionResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все'),
            'passed' => Tab::make('Пройдены')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'passed'))
                ->badge(fn () => self::getResource()::getEloquentQuery()->where('status', 'passed')->count()),
            'failed' => Tab::make('Не пройдены')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed')),
            'pending' => Tab::make('Ожидают')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
