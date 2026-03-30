<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources\GymResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditGym extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = GymResource::class;
}
