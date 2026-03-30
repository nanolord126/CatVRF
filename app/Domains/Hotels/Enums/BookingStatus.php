<?php declare(strict_types=1);

namespace App\Domains\Hotels\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case PENDING = 'pending';
        case CONFIRMED = 'confirmed';
        case ACTIVE = 'active';
        case COMPLETED = 'completed';
        case CANCELLED = 'cancelled';
        case FAILED = 'failed';

        public function label(): string
        {
            return match ($this) {
                self::PENDING => 'Ожидает оплаты',
                self::CONFIRMED => 'Подтверждено',
                self::ACTIVE => 'Проживание',
                self::COMPLETED => 'Завершено',
                self::CANCELLED => 'Отменено',
                self::FAILED => 'Ошибка платежа',
            };
        }
}
