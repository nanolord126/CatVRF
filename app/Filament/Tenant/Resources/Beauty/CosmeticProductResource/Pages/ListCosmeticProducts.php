<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\CosmeticProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListCosmeticProducts extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CosmeticProductResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\CreateAction::make(),
            ];
        }
}
