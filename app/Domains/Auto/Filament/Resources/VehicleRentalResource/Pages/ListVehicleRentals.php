<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListVehicleRentals extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VehicleRentalResource::class;

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
                'active' => \Filament\Resources\Components\Tab::make('Активные')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                    ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'active')->count())
                    ->badgeColor('success'),
                'pending' => \Filament\Resources\Components\Tab::make('Ожидают')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
                'completed' => \Filament\Resources\Components\Tab::make('Завершено')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            ];
        }
}
