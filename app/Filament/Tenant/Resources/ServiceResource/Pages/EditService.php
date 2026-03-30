<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ServiceResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
        }
}
