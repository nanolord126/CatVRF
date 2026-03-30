<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditServiceCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceCategoryResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\DeleteAction::make()];
        }
}
