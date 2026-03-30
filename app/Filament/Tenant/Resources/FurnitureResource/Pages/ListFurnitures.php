<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListFurnitures extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FurnitureResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\CreateAction::make(),
            ];
        }
}
