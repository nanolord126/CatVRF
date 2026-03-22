<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListVehicleInspections extends ListRecords
{
    protected static string $resource = VehicleInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('Все'),
            'passed' => \Filament\Resources\Components\Tab::make('Пройдены')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'passed'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'passed')->count()),
            'failed' => \Filament\Resources\Components\Tab::make('Не пройдены')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed')),
            'pending' => \Filament\Resources\Components\Tab::make('Ожидают')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
        ];
    }
}
