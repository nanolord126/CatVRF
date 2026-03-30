<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TuningProjectResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListTuningProjects extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TuningProjectResource::class;

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
                'pending' => \Filament\Resources\Components\Tab::make('Ожидают')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
                'in_progress' => \Filament\Resources\Components\Tab::make('В работе')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                    ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'in_progress')->count())
                    ->badgeColor('info'),
                'completed' => \Filament\Resources\Components\Tab::make('Завершено')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            ];
        }
}
