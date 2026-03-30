<?php declare(strict_types=1);

namespace Modules\Beauty\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case PENDING = 'pending';
        case CONFIRMED = 'confirmed';
        case UNPAID = 'unpaid';
        case COMPLETED = 'completed';
        case CANCELLED = 'cancelled';
        case NO_SHOW = 'no_show';
    
        public function label(): string
        {
            return match($this) {
                self::PENDING => 'Ожидание',
                self::CONFIRMED => 'Подтверждено',
                self::UNPAID => 'Не оплачено',
                self::COMPLETED => 'Завершено',
                self::CANCELLED => 'Отменено',
                self::NO_SHOW => 'Не явилась',
            };
        }
    
        public function color(): string
        {
            return match($this) {
                self::PENDING => 'warning',
                self::CONFIRMED => 'success',
                self::UNPAID => 'danger',
                self::COMPLETED => 'success',
                self::CANCELLED => 'secondary',
                self::NO_SHOW => 'danger',
            };
        }
}
