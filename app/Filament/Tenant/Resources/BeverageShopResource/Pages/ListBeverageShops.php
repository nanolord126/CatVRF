<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShopResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBeverageShops extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeverageShopResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Register New Venue')
                    ->icon('heroicon-o-plus'),
            ];
        }
}
