<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListServiceWarranties extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
