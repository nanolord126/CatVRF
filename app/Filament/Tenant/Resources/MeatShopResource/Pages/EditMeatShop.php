<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShopResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditMeatShop extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MeatShopResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\DeleteAction::make(),
            ];
        }
}
