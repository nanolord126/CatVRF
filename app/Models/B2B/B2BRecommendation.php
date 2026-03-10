<?php

namespace App\Models\B2B;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\Tenant;
use App\Models\B2B\Supplier;
use App\Traits\HasEcosystemTracing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model representing a B2B AI Recommendation for a buyer or supplier.
 */
class B2BRecommendation extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use HasFactory, SoftDeletes, HasEcosystemTracing;

    protected $table = 'b2b_recommendations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'supplier_id',
        'recommendable_type',
        'recommendable_id',
        'match_score',
        'reasoning',
        'type',
        'embeddings_version',
        'telemetry_context',
        'correlation_id'
    ];

    protected $casts = [
        'reasoning' => 'json',
        'telemetry_context' => 'json',
        'match_score' => 'float',
    ];

    /**
     * Relationship to the recommended object (Product, Supplier, Tenant).
     */
    public function recommendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for a specific buyer/tenant.
     */
    public function scopeForBuyer(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId)->where('type', 'SupplierBuy');
    }

    /**
     * Scope for a specific supplier/manufacturer.
     */
    public function scopeForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId)->where('type', 'TenantSell');
    }

    /**
     * AI-Driven filter for high similarity.
     */
    public function scopeHighConfidence(Builder $query, float $threshold = 0.85): Builder
    {
        return $query->where('match_score', '>=', $threshold);
    }
}







