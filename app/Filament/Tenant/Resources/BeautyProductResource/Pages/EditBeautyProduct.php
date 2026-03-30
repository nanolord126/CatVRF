<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditBeautyProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeautyProductResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
        }
}
