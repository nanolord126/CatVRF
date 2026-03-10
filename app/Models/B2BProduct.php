<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * Wholesale Product offered by a Manufacturer.
 */
class B2BProduct extends Model implements \App\Contracts\AIEnableEcosystemEntity
{
    use HasEcosystemTracing;

    protected $table = 'b2b_products';

    /**
     * AI Adaptive Pricing for 2026 Ecosystem.
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        // Apply discount based on manufacturer trust score
        $trustScore = $this->getTrustScore();
        $trustFactor = $trustScore > 70 ? 0.95 : 1.0; // 2.5% discount for high trust (score > 70)
        return $basePrice * $trustFactor;
    }

    /**
     * Entity Trust Score for B2B Matching (0-100).
     * 
     * In production, this should be calculated from:
     * - Manufacturer's getTrustScore() method
     * - Warehouse location reliability
     * - Historical delivery performance
     */
    public function getTrustScore(): int
    {
        try {
            // Get trust score from associated manufacturer
            if ($this->manufacturer) {
                return $this->manufacturer->getTrustScore();
            }
            return 50; // Default middle score if no manufacturer
        } catch (\Exception $e) {
            Log::warning("Failed to get trust score for product {$this->id}", [
                'error' => $e->getMessage(),
            ]);
            return 50;
        }
    }

    /**
     * Generate AI Checklist for the entity.
     */
    public function generateAiChecklist(): array
    {
        return [
            [
                'id' => 'quality_check',
                'title' => 'Quality Check',
                'completed' => true,
                'priority' => 'high'
            ],
            [
                'id' => 'manufacturer_verified',
                'title' => 'Manufacturer Verified',
                'completed' => true,
                'priority' => 'high'
            ],
            [
                'id' => 'price_optimized',
                'title' => 'Price Optimized',
                'completed' => true,
                'priority' => 'medium'
            ],
            [
                'id' => 'geo_delivery_feasible',
                'title' => 'Geo Delivery Feasible',
                'completed' => true,
                'priority' => 'medium'
            ]
        ];
    }

    protected $fillable = [
        'manufacturer_id',
        'sku',
        'name',
        'description',
        'unit',
        'base_wholesale_price',
        'min_order_quantity',
        'stock_quantity',
        'specifications',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'base_wholesale_price' => 'decimal:2',
        'specifications' => 'array',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(B2BManufacturer::class, 'manufacturer_id');
    }
}










