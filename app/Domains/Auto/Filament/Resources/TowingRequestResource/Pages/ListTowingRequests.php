declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use App\Domains\Auto\Filament\Resources\TowingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final /**
 * ListTowingRequests
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListTowingRequests extends ListRecords
{
    protected static string $resource = TowingRequestResource::class;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'pending')->count())
                ->badgeColor('warning'),
            'in_progress' => \Filament\Resources\Components\Tab::make('В процессе')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'in_progress')->count())
                ->badgeColor('info'),
            'completed' => \Filament\Resources\Components\Tab::make('Завершено')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
        ];
    }
}
