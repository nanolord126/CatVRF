<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * AI Pricing Calculator Service
 *
 * Calculates costs for AI operations based on usage metrics.
 * Supports multiple pricing tiers, volume discounts, and dynamic pricing.
 *
 * Features:
 * - Per-operation pricing (vision, chat, embedding, etc.)
 * - Volume-based discounts
 * - Tenant-specific pricing tiers
 * - Real-time cost calculation
 * - Historical cost tracking
 *
 * Strictly follows CatVRF rules:
 * - No facades, uses dependency injection
 * - Configurable pricing via config repository
 * - Comprehensive logging for billing accuracy
 * - All files exceed 60 lines
 */
final readonly class AIPricingCalculatorService
{
    private const string DEFAULT_PRICING_KEY = 'ai.pricing';
    private const string VOLUME_DISCOUNT_KEY = 'ai.volume_discounts';

    /**
     * Calculate cost for a single AI operation
     *
     * @param string $operation Operation type (vision, chat, embedding, etc.)
     * @param int $tokens Number of tokens used (for text-based operations)
     * @param int $images Number of images processed (for vision operations)
     * @param int|null $tenantId Tenant ID for tenant-specific pricing
     * @return float Cost in currency
     */
    public function calculateCost(
        string $operation,
        int $tokens = 0,
        int $images = 0,
        ?int $tenantId = null
    ): float {
        $basePrice = $this->getBasePrice($operation, $tenantId);
        $multiplier = $this->calculateMultiplier($operation, $tokens, $images);
        $volumeDiscount = $this->getVolumeDiscount($tenantId);

        $cost = $basePrice * $multiplier * (1 - $volumeDiscount);

        $this->logger->debug('AI cost calculated', [
            'operation' => $operation,
            'tokens' => $tokens,
            'images' => $images,
            'tenant_id' => $tenantId,
            'base_price' => $basePrice,
            'multiplier' => $multiplier,
            'volume_discount' => $volumeDiscount,
            'final_cost' => $cost,
        ]);

        return round($cost, 6);
    }

    /**
     * Get base price for operation
     */
    private function getBasePrice(string $operation, ?int $tenantId = null): float
    {
        $pricing = $this->config->get(self::DEFAULT_PRICING_KEY, []);

        if ($tenantId !== null) {
            $tenantPricing = $this->config->get("ai.tenant_pricing.{$tenantId}", []);
            if (isset($tenantPricing[$operation])) {
                return (float) $tenantPricing[$operation];
            }
        }

        return (float) ($pricing[$operation] ?? $this->getDefaultPrice($operation));
    }

    /**
     * Get default price for operation if not configured
     */
    private function getDefaultPrice(string $operation): float
    {
        return match ($operation) {
            'vision' => 0.01,
            'chat' => 0.002,
            'embedding' => 0.0001,
            'text_generation' => 0.003,
            'image_generation' => 0.02,
            'audio_transcription' => 0.006,
            'audio_translation' => 0.008,
            'moderation' => 0.001,
            default => 0.01,
        };
    }

    /**
     * Calculate multiplier based on usage volume
     */
    private function calculateMultiplier(string $operation, int $tokens, int $images): float
    {
        return match ($operation) {
            'vision' => max(1.0, $images),
            'chat', 'text_generation' => max(1.0, $tokens / 1000),
            'embedding' => max(1.0, $tokens / 1000),
            'audio_transcription', 'audio_translation' => max(1.0, $tokens / 60),
            'image_generation' => max(1.0, $images),
            'moderation' => max(1.0, $tokens / 1000),
            default => 1.0,
        };
    }

    /**
     * Get volume discount for tenant
     */
    private function getVolumeDiscount(?int $tenantId = null): float
    {
        $discounts = $this->config->get(self::VOLUME_DISCOUNT_KEY, []);

        if ($tenantId !== null) {
            $tenantDiscounts = $this->config->get("ai.tenant_discounts.{$tenantId}", []);
            if (!empty($tenantDiscounts)) {
                return (float) max($tenantDiscounts);
            }
        }

        return (float) ($discounts['default'] ?? 0.0);
    }

    /**
     * Calculate estimated cost before operation execution
     */
    public function estimateCost(
        string $operation,
        int $estimatedTokens = 0,
        int $estimatedImages = 0,
        ?int $tenantId = null
    ): float {
        return $this->calculateCost($operation, $estimatedTokens, $estimatedImages, $tenantId);
    }

    /**
     * Calculate batch cost for multiple operations
     */
    public function calculateBatchCost(array $operations): float
    {
        $totalCost = 0.0;

        foreach ($operations as $op) {
            $totalCost += $this->calculateCost(
                $op['operation'],
                $op['tokens'] ?? 0,
                $op['images'] ?? 0,
                $op['tenant_id'] ?? null
            );
        }

        $this->logger->info('Batch cost calculated', [
            'operation_count' => count($operations),
            'total_cost' => $totalCost,
        ]);

        return round($totalCost, 6);
    }

    /**
     * Get pricing tier for tenant
     */
    public function getPricingTier(int $tenantId): string
    {
        $tier = $this->config->get("ai.tenant_tiers.{$tenantId}", 'standard');

        return $tier;
    }

    /**
     * Set custom pricing for tenant
     */
    public function setTenantPricing(int $tenantId, array $pricing): void
    {
        $this->config->set("ai.tenant_pricing.{$tenantId}", $pricing);

        $this->logger->info('Tenant pricing updated', [
            'tenant_id' => $tenantId,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Set volume discount for tenant
     */
    public function setTenantDiscount(int $tenantId, float $discount): void
    {
        $this->config->set("ai.tenant_discounts.{$tenantId}", [$discount]);

        $this->logger->info('Tenant discount updated', [
            'tenant_id' => $tenantId,
            'discount' => $discount,
        ]);
    }

    /**
     * Get all pricing information for tenant
     */
    public function getTenantPricingInfo(int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'tier' => $this->getPricingTier($tenantId),
            'pricing' => $this->config->get("ai.tenant_pricing.{$tenantId}", []),
            'discounts' => $this->config->get("ai.tenant_discounts.{$tenantId}", []),
            'volume_discount' => $this->getVolumeDiscount($tenantId),
        ];
    }

    /**
     * Calculate cost savings with volume discount
     */
    public function calculateSavings(string $operation, int $tokens, int $images, ?int $tenantId = null): array
    {
        $costWithoutDiscount = $this->calculateCost($operation, $tokens, $images, null);
        $costWithDiscount = $this->calculateCost($operation, $tokens, $images, $tenantId);
        $savings = $costWithoutDiscount - $costWithDiscount;
        $percentage = $costWithoutDiscount > 0 ? ($savings / $costWithoutDiscount) * 100 : 0;

        return [
            'cost_without_discount' => round($costWithoutDiscount, 6),
            'cost_with_discount' => round($costWithDiscount, 6),
            'savings' => round($savings, 6),
            'savings_percentage' => round($percentage, 2),
        ];
    }

    /**
     * Validate pricing configuration
     */
    public function validatePricingConfig(): bool
    {
        $pricing = $this->config->get(self::DEFAULT_PRICING_KEY, []);

        if (empty($pricing)) {
            $this->logger->warning('AI pricing configuration is empty, using defaults');
            return false;
        }

        foreach ($pricing as $operation => $price) {
            if (!is_numeric($price) || $price < 0) {
                $this->logger->error('Invalid price configuration', [
                    'operation' => $operation,
                    'price' => $price,
                ]);
                return false;
            }
        }

        $this->logger->info('AI pricing configuration validated');
        return true;
    }

    /**
     * Get pricing breakdown for invoice
     */
    public function getPricingBreakdown(array $operations): array
    {
        $breakdown = [];
        $totalCost = 0.0;

        foreach ($operations as $index => $op) {
            $cost = $this->calculateCost(
                $op['operation'],
                $op['tokens'] ?? 0,
                $op['images'] ?? 0,
                $op['tenant_id'] ?? null
            );

            $breakdown[] = [
                'index' => $index,
                'operation' => $op['operation'],
                'tokens' => $op['tokens'] ?? 0,
                'images' => $op['images'] ?? 0,
                'cost' => $cost,
            ];

            $totalCost += $cost;
        }

        return [
            'breakdown' => $breakdown,
            'total_cost' => round($totalCost, 6),
            'operation_count' => count($operations),
        ];
    }

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger
    ) {}
}
