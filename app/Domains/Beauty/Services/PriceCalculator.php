<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PriceCalculator extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Рассчитать финальную цену услуги с учетом скидок и опыта мастера.
         */
        public function calculateFinalPrice(BeautyService $service, Master $master, array $context = []): int
        {
            $basePrice = $service->price;

            // Наценка за опыт мастера
            $experienceMultiplier = 1.0;
            if ($master->experience_years > 10) {
                $experienceMultiplier = 1.2;
            } elseif ($master->experience_years > 5) {
                $experienceMultiplier = 1.1;
            }

            $price = (int) ($basePrice * $experienceMultiplier);

            // Применение промокодов (если есть в контексте)
            if (isset($context['promo_discount_percent'])) {
                $price = (int) ($price * (1 - $context['promo_discount_percent'] / 100));
            }

            return $price;
        }
}
