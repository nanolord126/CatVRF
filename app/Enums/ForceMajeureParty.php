<?php declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ForceMajeureParty extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case CLIENT = 'client';       // Клиент (болезнь, смерть родственника и т.д.)
        case SALON = 'salon';         // Салон (отключение света, воды, болезнь мастера)
        case PLATFORM = 'platform';   // Платформа (технический сбой, ошибка биллинга)
        case EXTERNAL = 'external';   // Внешние факторы (стихийные бедствия, война, госорганы)
}
