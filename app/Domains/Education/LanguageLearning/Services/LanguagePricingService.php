<?php

declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use App\Domains\Education\LanguageLearning\Models\LanguageCourse;
use Illuminate\Support\Facades\Log;

/**
 * Калькулятор цен для изучения языков (2026 Algorithm).
 * Учитывает вертикаль, тип обучения и обороты тенанта.
 */
final readonly class LanguagePricingService
{
    /**
     * Расчет стоимости курса с учетом B2B/B2C и сезонных скидок.
     */
    public function calculateCoursePrice(int $basePrice, string $clientType, array $context = []): int
    {
        $price = $basePrice;

        // B2B скидка 15%
        if ($clientType === 'b2b') {
            $price = (int)($price * 0.85);
        }

        // Надбавка за интенсивность
        if (($context['type'] ?? '') === 'intensive') {
            $price = (int)($price * 1.25);
        }

        // Скидка за пакетный выкуп (модульная оплата)
        if (($context['is_package'] ?? false)) {
            $price = (int)($price * 0.90);
        }

        Log::channel('audit')->info('Language pricing calculated', [
            'base' => $basePrice,
            'final' => $price,
            'type' => $clientType,
        ]);

        return $price;
    }

    /**
     * Расчет комиссии платформы (Standard: 14%).
     */
    public function calculatePlatformFee(int $amount): int
    {
        return (int)($amount * 0.14);
    }
}
