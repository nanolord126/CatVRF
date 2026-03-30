<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListVehicleInspections extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
