<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListCarWashBookings extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarWashBookingResource::class;

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

                'pending' => Tab::make('В ожидании')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                    ->badge(static::getResource()::getEloquentQuery()->where('status', 'pending')->count())
                    ->badgeColor('warning'),

                'in_progress' => Tab::make('В процессе')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                    ->badge(static::getResource()::getEloquentQuery()->where('status', 'in_progress')->count())
                    ->badgeColor('info'),

                'completed' => Tab::make('Завершены')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

                'cancelled' => Tab::make('Отменены')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
            ];
        }
}
