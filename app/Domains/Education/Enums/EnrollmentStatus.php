<?php declare(strict_types=1);

namespace App\Domains\Education\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case PENDING = 'pending';
        case ACTIVE = 'active';
        case COMPLETED = 'completed';
        case REJECTED = 'rejected';
        case REFUNDED = 'refunded';

        public function canAccessLessons(): bool
        {
            return $this === self::ACTIVE || $this === self::COMPLETED;
        }
}
