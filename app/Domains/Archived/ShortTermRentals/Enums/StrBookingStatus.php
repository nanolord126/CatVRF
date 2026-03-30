<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrBookingStatus extends Model
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


            return match($this) {


                self::PENDING => 'Ожидание оплаты',


                self::CONFIRMED => 'Подтверждено (предоплата)',


                self::ACTIVE => 'Гость проживает',


                self::COMPLETED => 'Завершено',


                self::CANCELLED => 'Отменено',


                self::FAILED => 'Ошибка системы',


            };


        }


        public function color(): string


        {


            return match($this) {


                self::PENDING => 'warning',


                self::CONFIRMED => 'success',


                self::ACTIVE => 'info',


                self::COMPLETED => 'gray',


                self::CANCELLED, self::FAILED => 'danger',


            };


        }
}
