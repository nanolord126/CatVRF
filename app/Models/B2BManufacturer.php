<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEcosystemTracing;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;
use App\Contracts\AIEnableEcosystemEntity;

/**
 * Manufacturer or Main Importer in the B2B Ecosystem.
 */
class B2BManufacturer extends Model implements Wallet, AIEnableEcosystemEntity
{
    use SoftDeletes, HasEcosystemTracing, HasWallet;

    protected $table = 'b2b_manufacturers';

    protected $fillable = [
        'name',
        'brand_name',
        'registration_number',
        'contact_email',
        'contact_phone',
        'legal_address',
        'category',
        'ai_trust_score',
        'geo_coverage',
        'is_active',
        'correlation_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ai_trust_score' => 'decimal:2',
        'geo_coverage' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(B2BProduct::class, 'manufacturer_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(WholesaleContract::class, 'manufacturer_id');
    }

    // AIEnableEcosystemEntity Implementation
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        // Simple logic: adjust price based on manufacturer trust score
        $adjustment = $this->ai_trust_score > 4 ? 0.95 : 1.0;
        return round($basePrice * $adjustment, 2);
    }

    public function getTrustScore(): float
    {
        return (float) $this->ai_trust_score;
    }

    public function generateAiChecklist(): array
    {
        return [
            'verify_registration_status' => true,
            'check_supply_consistency' => $this->ai_trust_score > 3,
            'review_partner_feedback' => true,
        ];
    }
}










