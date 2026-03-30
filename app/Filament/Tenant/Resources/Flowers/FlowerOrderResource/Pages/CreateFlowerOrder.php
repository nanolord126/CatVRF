<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateFlowerOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FlowerOrderResource::class;
}
