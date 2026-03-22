<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages;

use App\Domains\Auto\Filament\Resources\ServiceWarrantyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListServiceWarranties extends ListRecords
{
    protected static string $resource = ServiceWarrantyResource::class;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('DATE_ADD(start_date, INTERVAL warranty_months MONTH) > NOW()')),
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
