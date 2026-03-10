<?php

namespace App\Models\RealEstate;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Traits\HasAutomation2026;

/**
 * Property Model < 45 lines.
 * Canon 2026: Land, Commercial, Rental, Business.
 */
class Property extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use HasAutomation2026;

    protected $fillable = [
        'tenant_id', 'type', 'name', 'area', 'price', 
        'geo_data', 'amenities', 'correlation_id', 'metadata'
    ];

    protected $casts = [
        'geo_data' => 'array',
        'amenities' => 'array',
        'metadata' => 'array',
        'area' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * Filter by vertical-specific logic (e.g. Real Estate Search).
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('tenant_id');
    }
}








