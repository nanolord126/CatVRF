<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VerticalServiceInterface extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Get the vertical code name (e.g., 'beauty', 'auto', 'food').
         */
        public function getVerticalName(): string;

        /**
         * Determine the base commission rate for the vertical.
         */
        public function getBaseCommissionRate(): float;
}
