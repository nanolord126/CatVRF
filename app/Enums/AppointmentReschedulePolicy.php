<?php declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentReschedulePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case UNLIMITED_FREE = 'unlimited_free'; // Без ограничений (VIP/Luxury)
        case ONCE_FREE_24H = 'once_free_24h';   // Один раз за 24ч (Стандарт)
        case ONCE_FREE_72H = 'once_free_72h';   // Один раз за 72ч (Групповые)
        case ONCE_FIXED_FEE = 'once_fixed_fee'; // Перенос с фиксированным сбором (Свадьбы/Корп)
        case NO_RESCHEDULE = 'no_reschedule';   // Перенос запрещен (Мастер-классы)
}
