<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;

use App\Domains\Auto\Filament\Resources\CarDetailingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListCarDetailings extends ListRecords
{
    protected static string $resource = CarDetailingResource::class;

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
            'in_progress' => \Filament\Resources\Components\Tab::make('В процессе')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),
            'completed' => \Filament\Resources\Components\Tab::make('Завершено')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
        ];
    }
}
