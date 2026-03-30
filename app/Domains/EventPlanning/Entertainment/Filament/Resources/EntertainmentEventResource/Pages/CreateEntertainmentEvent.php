<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentEventResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateEntertainmentEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = EntertainmentEventResource::class;
}
