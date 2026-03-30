<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrDepositStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case PENDING = 'pending';


        case HELD = 'held';


        case RELEASED = 'released';


        case CHARGED = 'charged';


        public function label(): string


        {


            return match($this) {


                self::PENDING => 'Ожидает холда',


                self::HELD => 'Вхолдирован на карте гостя',


                self::RELEASED => 'Возвращен гостю',


                self::CHARGED => 'Списан в счет ущерба',


            };


        }
}
