<?php declare(strict_types=1);

namespace App\Domains\Beauty\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReschedulePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case STANDARD = 'standard';
        case PREMIUM  = 'premium';  // Гибкие условия за доп. плату при бронировании

        /**
         * Получить базовый процент комиссии за перенос.
         */
        public function getBaseFeePercent(float $hoursBefore): int
        {
            return match (true) {
                $hoursBefore >= 48 => 0,
                $hoursBefore >= 24 => 10,
                $hoursBefore >= 12 => 25,
                $hoursBefore >= 4  => 40,
                default            => 100, // Перенос невозможен (фактически отмена со 100% штрафом)
            };
        }
}
