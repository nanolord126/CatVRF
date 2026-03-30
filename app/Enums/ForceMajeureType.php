<?php declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ForceMajeureType extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case NATURAL_DISASTER = 'natural_disaster'; // Стихийные бедствия
        case UTILITY_FAILURE = 'utility_failure';   // Аварии ЖКХ (свет, вода в салоне)
        case STAFF_ILLNESS = 'staff_illness';       // Болезнь мастера (со справкой)
        case CLIENT_ILLNESS = 'client_illness';     // Болезнь клиента / Госпитализация
        case BEREAVEMENT = 'bereavement';           // Смерть близкого родственника
        case GOVERNMENT_ACTION = 'government_action'; // Решения госорганов / Закрытие
        case MILITARY_EMERGENCY = 'military_emergency'; // Военное положение / ЧС
        case PLATFORM_FAILURE = 'platform_failure'; // Технический сбой платформы
        case OTHER_OFFICIAL = 'other_official';     // Иное (подтвержденное документами)
}
