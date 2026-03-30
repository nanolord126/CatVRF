<?php declare(strict_types=1);

namespace Modules\Beauty\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SalonStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case OPEN = 'open';
        case CLOSED = 'closed';
        case TEMPORARILY_CLOSED = 'temporarily_closed';
        case MAINTENANCE = 'maintenance';
    
        public function label(): string
        {
            return match($this) {
                self::OPEN => 'Открыто',
                self::CLOSED => 'Закрыто',
                self::TEMPORARILY_CLOSED => 'Временно закрыто',
                self::MAINTENANCE => 'На обслуживании',
            };
        }
    
        public function isOpen(): bool
        {
            return $this === self::OPEN;
        }
}
