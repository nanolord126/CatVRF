<?php declare(strict_types=1);

namespace App\Enums\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConstructorType extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case INTERIOR = 'interior';
        case BEAUTY_LOOK = 'beauty_look';
        case OUTFIT = 'outfit';
        case CAKE = 'cake';
        case MENU = 'menu';
}
