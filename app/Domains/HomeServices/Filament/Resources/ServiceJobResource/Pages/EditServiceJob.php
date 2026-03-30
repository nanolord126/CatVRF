<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditServiceJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceJobResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\DeleteAction::make()];
        }
}
