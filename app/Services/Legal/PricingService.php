<?php

declare(strict_types=1);

namespace App\Services\Legal;

use App\Models\Legal\Lawyer;
use App\Models\Legal\LegalService;
use Illuminate\Support\Facades\Log;

/**
 * PricingService - Vertical LegalServices (CAR 2026)
 * Handles price calculations for B2B/B2C, urgent services, and regional factors.
 * Includes Fraud check and audit logging.
 */
final readonly class PricingService
{
    /**
     * Calculate consultation price based on lawyer, complexity, and urgency.
     */
    public function calculateConsultationPrice(
        Lawyer $lawyer,
        string $complexity = 'standard',
        bool $isUrgent = false,
        bool $isB2B = false,
        string $correlationId = null
    ): int {
        $basePrice = $lawyer->consultation_price;
        $multiplier = 1.0;

        // Complexity Multiplier
        $multiplier *= match ($complexity) {
            'high' => 1.5,
            'special' => 2.0,
            default => 1.0,
        };

        // Urgency Premium
        if ($isUrgent) {
            $multiplier += 0.3; // +30%
        }

        // B2B Discount (Corporate rate)
        if ($isB2B) {
            $multiplier -= 0.15; // -15%
        }

        $finalPrice = (int) ($basePrice * $multiplier);

        Log::channel('audit')->info('Legal consultation price calculated', [
            'lawyer_id' => $lawyer->id,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'multipliers' => [
                'complexity' => $complexity,
                'urgent' => $isUrgent,
                'b2b' => $isB2B,
            ],
            'correlation_id' => $correlationId,
        ]);

        return $finalPrice;
    }

    /**
     * Calculate document preparation price.
     */
    public function calculateServicePrice(
        LegalService $service,
        int $pageCount = 1,
        bool $isB2B = false,
        string $correlationId = null
    ): int {
        $basePrice = $service->base_price;
        
        // Volume pricing for multi-page documents
        $volumeMultiplier = 1.0;
        if ($pageCount > 5) {
            $volumeMultiplier = 1.2;
        } elseif ($pageCount > 15) {
            $volumeMultiplier = 1.5;
        }

        $finalPrice = (int) ($basePrice * $volumeMultiplier);

        if ($isB2B) {
            $finalPrice = (int) ($finalPrice * 0.85); // 15% discount for legal entities
        }

        Log::channel('audit')->info('Legal service price calculated', [
            'service_id' => $service->id,
            'page_count' => $pageCount,
            'final_price' => $finalPrice,
            'correlation_id' => $correlationId,
        ]);

        return $finalPrice;
    }

    /**
     * Check for suspicious pricing anomalies.
     */
    public function validatePriceForFraud(int $price, string $type): bool
    {
        // Simple heuristic: legal consultation shouldn't exceed 500,000 RUB or be less than 1 rub
        if ($price > 50000000 || $price < 100) {
            Log::channel('fraud_alert')->warning('Suspicious legal price detected', [
                'price' => $price,
                'type' => $type,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Format price for display in RUB.
     */
    public function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ' ') . ' ₽';
    }
}
