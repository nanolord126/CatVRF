<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PropertyResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditProperty extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PropertyResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ];
        }
}
