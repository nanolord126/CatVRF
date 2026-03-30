<?php declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentType extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case STANDARD = 'standard';
        case GROUP = 'group';
        case WEDDING = 'wedding';
        case KIDS_PARTY = 'kids_party';
        case CORPORATE = 'corporate';
        case PHOTO_SESSION = 'photo_session';
        case MASTER_CLASS = 'master_class';
        case OUTDOOR = 'outdoor'; // Выездные услуги
        case GIFT_CERTIFICATE = 'gift_certificate';
        case SUBSCRIPTION = 'subscription';
        case LUXURY = 'luxury';
        case AI_CONSTRUCTED = 'ai_constructed';
}
