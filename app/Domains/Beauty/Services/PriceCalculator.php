<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

/**
 * PriceCalculator — расчёт цены услуги в копейках.
 *
 * Учитывает:
 *  - Опыт мастера (+10% за 5+ лет, +20% за 10+ лет)
 *  - Скидка по промокоду (0-100%)
 */
final readonly class PriceCalculator
{
    /**
     * Расчитать цену в копейках.
     *
     * @param array<string, mixed> $context Контекст (напр., promo_discount_percent)
     */
    public function calculateFinalPrice(
        int   $basePriceKopecks,
        int   $masterExperienceYears,
        array $context = [],
    ): int {
        $price = $this->applyExperienceMultiplier($basePriceKopecks, $masterExperienceYears);

        if (isset($context['promo_discount_percent'])) {
            $price = $this->applyPromoDiscount($price, (int) $context['promo_discount_percent']);
        }

        return max(0, $price);
    }

    /**
     * Применить коэффициент опыта.
     */
    public function applyExperienceMultiplier(int $priceKopecks, int $experienceYears): int
    {
        $multiplier = match (true) {
            $experienceYears > 10 => 1.20,
            $experienceYears > 5  => 1.10,
            default               => 1.00,
        };

        return (int) ($priceKopecks * $multiplier);
    }

    /**
     * Применить скидку промокода (в процентах 0-100).
     *
     * @throws \InvalidArgumentException Если процент выходит за диапазон [0, 100].
     */
    public function applyPromoDiscount(int $priceKopecks, int $discountPercent): int
    {
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new \InvalidArgumentException("Процент скидки должен быть от 0 до 100.");
        }

        return (int) ($priceKopecks * (1 - $discountPercent / 100));
    }
}
