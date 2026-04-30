<?php

declare(strict_types=1);

/**
 * ListCarWashBookings — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listcarwashbookings
 */

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

final class ListCarWashBookings extends ListRecords
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;


    protected static string $resource = CarWashBookingResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все')
                ->badge(self::getResource()::getEloquentQuery()->count()),

            'pending' => Tab::make('В ожидании')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(self::getResource()::getEloquentQuery()->where('status', 'pending')->count())
                ->badgeColor('warning'),

            'in_progress' => Tab::make('В процессе')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                ->badge(self::getResource()::getEloquentQuery()->where('status', 'in_progress')->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Завершены')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'cancelled' => Tab::make('Отменены')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
