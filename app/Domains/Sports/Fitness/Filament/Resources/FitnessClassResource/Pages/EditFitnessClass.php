<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources\FitnessClassResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditFitnessClass extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FitnessClassResource::class;
}
