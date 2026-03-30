<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewAutoPart extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),
            ];
        }
}
