<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyBaseService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @return string
         */
        public function getVerticalName(): string
        {
            return 'beauty';
        }

        /**
         * Beauty standard commission is 14%.
         * (With reduction to 10% or 12% if migrated from Dikidi).
         *
         * @return float
         */
        public function getBaseCommissionRate(): float
        {
            // 14% is represented as 14.0 or 0.14. Using 0.14 for calculations.
            return 0.14;
        }
}
