<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FoodBaseService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @return string
         */
        public function getVerticalName(): string
        {
            return 'food';
        }

        /**
         * Food vertical standard commission is 14%.
         *
         * @return float
         */
        public function getBaseCommissionRate(): float
        {
            return 0.14;
        }
}
