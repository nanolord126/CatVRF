<?php

declare(strict_types=1);

namespace App\Services\Dental;

use App\Models\Dental\DentalService as DentalModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pricing Service (Main Calculator for Dental Billing).
 * Strictly follows CANON 2026: Multi-tenant pricing rules and Bulk calculations.
 */
final readonly class PricingService
{
    public function __construct(
        private string $correlation_id = ''
    ) {
        $this->correlation_id = empty($correlation_id) ? (string) Str::uuid() : $correlation_id;
    }

    /**
     * Calculate total price for a collection of dental services.
     */
    public function calculateTotal(Collection $services): int
    {
        $total = 0;
        foreach ($services as $service) {
            $total += $service->base_price;
        }

        // Apply automatic bulk discounts if relevant
        $discount = $this->getBulkDiscount($services->count(), $total);

        return max($total - $discount, 0);
    }

    /**
     * Calculate individual service price with context (e.g., insurance, promo).
     */
    public function calculatePrice(DentalModel $service, array $context = []): int
    {
        $price = $service->base_price;

        // Apply context-based adjustments (e.g., B2B vs B2C)
        if (isset($context['is_b2b']) && $context['is_b2b']) {
            $price = (int) ($price * 0.9); // 10% B2B corporate discount
        }

        // Apply insurance coverage (if provided)
        if (isset($context['insurance_coverage_percent'])) {
            $coverage = (int) ($price * ($context['insurance_coverage_percent'] / 100));
            $price -= $coverage;
        }

        return max($price, 0);
    }

    /**
     * Bulk discount logic for complex dental treatment packages.
     */
    private function getBulkDiscount(int $count, int $subtotal): int
    {
        if ($count >= 10 || $subtotal > 1000000) { // > 100,000 rub
            return (int) ($subtotal * 0.05); // 5% Discount for large treatments
        }

        if ($count >= 5) {
            return (int) ($subtotal * 0.03); // 3% Discount
        }

        return 0;
    }

    /**
     * Format price for display in rubles.
     */
    public function formatForDisplay(int $priceInKopecks): string
    {
        return number_format($priceInKopecks / 100, 2, '.', ' ') . ' ₽';
    }

    /**
     * Check if a treatment plan requires prepayment based on its budget.
     */
    public function requiresPrepayment(int $totalBudget): bool
    {
        // Require prepayment for treatments over 50,000 rub
        return $totalBudget > 5000000;
    }

    /**
     * Log pricing operation for audit.
     */
    public function logPriceCalculation(int $finalPrice, array $details): void
    {
        Log::channel('audit')->info('Dental price calculated', array_merge($details, [
            'final_price' => $finalPrice,
            'correlation_id' => $this->correlation_id,
        ]));
    }
}
