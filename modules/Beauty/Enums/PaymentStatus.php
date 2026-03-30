<?php declare(strict_types=1);

namespace Modules\Beauty\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case PENDING = 'pending';
        case CONFIRMED = 'confirmed';
        case FAILED = 'failed';
        case REFUNDED = 'refunded';
        case CANCELLED = 'cancelled';
    
        public function label(): string
        {
            return match($this) {
                self::PENDING => 'Ожидание платежа',
                self::CONFIRMED => 'Оплачено',
                self::FAILED => 'Ошибка платежа',
                self::REFUNDED => 'Возвращено',
                self::CANCELLED => 'Отменено',
            };
        }
    
        public function color(): string
        {
            return match($this) {
                self::PENDING => 'warning',
                self::CONFIRMED => 'success',
                self::FAILED => 'danger',
                self::REFUNDED => 'info',
                self::CANCELLED => 'secondary',
            };
        }
}
