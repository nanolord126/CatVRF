<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListFlowerProducts extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FlowerProductResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
            ];
        }
}
