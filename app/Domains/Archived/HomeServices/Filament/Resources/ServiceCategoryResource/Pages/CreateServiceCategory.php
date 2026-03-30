<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Filament\Resources\ServiceCategoryResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateServiceCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceCategoryResource::class;
}
