<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;

use App\Domains\Auto\Filament\Resources\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Список автозапчастей с фильтрацией по остаткам и статусу.
 * Production 2026.
 */
final class ListAutoParts extends ListRecords
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все')
                ->badge(static::getResource()::getEloquentQuery()->count()),

            'low_stock' => Tab::make('Низкий остаток')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('current_stock < min_stock_threshold'))
                ->badge(static::getResource()::getEloquentQuery()->whereRaw('current_stock < min_stock_threshold')->count())
                ->badgeColor('danger'),

            'out_of_stock' => Tab::make('Нет в наличии')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_stock', '<=', 0))
                ->badge(static::getResource()::getEloquentQuery()->where('current_stock', '<=', 0)->count())
                ->badgeColor('warning'),

            'available' => Tab::make('В наличии')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_stock', '>', 0)),
        ];
    }
}
