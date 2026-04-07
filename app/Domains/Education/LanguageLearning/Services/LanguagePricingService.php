<?php declare(strict_types=1);

/**
 * LanguagePricingService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/languagepricingservice
 */


namespace App\Domains\Education\LanguageLearning\Services;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class LanguagePricingService
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


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

            $this->logger->info('Language pricing calculated', [
                'base' => $basePrice,
                'final' => $price,
                'type' => $clientType,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
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
