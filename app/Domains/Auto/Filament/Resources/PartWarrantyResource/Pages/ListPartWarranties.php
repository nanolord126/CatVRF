declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;

use App\Domains\Auto\Filament\Resources\PartWarrantyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final /**
 * ListPartWarranties
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListPartWarranties extends ListRecords
{
    protected static string $resource = PartWarrantyResource::class;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('DATE_A> NOW()')),
            'no_claims' => \Filament\Resources\Components\Tab::make('Без претензий')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'none')),
            'pending_claims' => \Filament\Resources\Components\Tab::make('Претензии на рассмотрении')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'pending'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('claim_status', 'pending')->count())
                ->badgeColor('warning'),
            'approved' => \Filament\Resources\Components\Tab::make('Одобренные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'approved')),
        ];
    }
}
