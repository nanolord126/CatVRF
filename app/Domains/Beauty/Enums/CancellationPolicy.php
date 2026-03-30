<?php declare(strict_types=1);

namespace App\Domains\Beauty\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CancellationPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case FLEXIBLE  = 'flexible';  // Мягкая: бесплатная отмена до 24ч
        case STRICT    = 'strict';    // Строгая: бесплатная отмена до 48ч
        case CORPORATE = 'corporate'; // Корпоративная (B2B): всегда штраф 10% за администрирование

        /**
         * Получить процент штрафа в зависимости от оставшегося времени (в часах).
         */
        public function getPenaltyPercent(float $hoursBefore): int
        {
            return match ($this) {
                self::FLEXIBLE => match (true) {
                    $hoursBefore >= 24 => 0,
                    $hoursBefore >= 4  => 30,
                    default            => 100,
                },
                self::STRICT => match (true) {
                    $hoursBefore >= 48 => 0,
                    $hoursBefore >= 24 => 30,
                    $hoursBefore >= 12 => 50,
                    default            => 100,
                },
                self::CORPORATE => match (true) {
                    $hoursBefore >= 48 => 10, // Админ-сбор
                    $hoursBefore >= 24 => 50,
                    default            => 100,
                },
            };
        }
}
