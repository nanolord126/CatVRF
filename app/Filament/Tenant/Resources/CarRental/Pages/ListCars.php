<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CarRental\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListCars extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarResource::class;

        /**
         * Actions: Comprehensive Vehicle Creation.
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('New Fleet Member')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
