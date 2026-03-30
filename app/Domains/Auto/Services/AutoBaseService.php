<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoBaseService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @return string
         */
        public function getVerticalName(): string
        {
            return 'auto';
        }

        /**
         * Auto vertical standard commission:
         * 15% + 5% fleet / 17.5% self-employed.
         * Retuning the base 15%.
         *
         * @return float
         */
        public function getBaseCommissionRate(): float
        {
            return 0.15;
        }
}
