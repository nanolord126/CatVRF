<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInsuranceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final /**
 * ListVehicleInsurances
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListVehicleInsurances extends ListRecords
{
    protected static string $resource = VehicleInsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('check_expiring')
                ->label('Проверить истекающие')
                ->action(function () {
                    $expiring = static::getResource()::getEloquentQuery()
                        ->where('status', 'active')
                        ->where('end_date', '<=', now()->addDays(30))
                        ->count();
                    
                    \Filament\Notifications\$this->notification->make()
                        ->title('Найдено полисов с истекающим сроком: ' . $expiring)
                        ->info()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('Все'),
            'active' => \Filament\Resources\Components\Tab::make('Активные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'expiring' => \Filament\Resources\Components\Tab::make('Истекают')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')->where('end_date', '<=', now()->addDays(30)))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'active')->where('end_date', '<=', now()->addDays(30))->count())
                ->badgeColor('warning'),
            'expired' => \Filament\Resources\Components\Tab::make('Истёкшие')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'expired')),
        ];
    }
}
