<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItemResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBeverageItems extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeverageItemResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Introduce Drink Item')
                    ->icon('heroicon-o-sparkles'),
            ];
        }
}
