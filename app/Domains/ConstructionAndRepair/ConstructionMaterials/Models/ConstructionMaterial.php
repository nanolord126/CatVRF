<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ConstructionMaterials\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConstructionMaterial extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'construction_materials';

        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id', 'name', 'sku', 'price', 'current_stock',
            'unit_type', 'consumption_per_m2', 'description', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'price' => 'integer',
            'current_stock' => 'integer',
            'consumption_per_m2' => 'float',
        ];

        protected static function booted(): void
        {
            $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Scope query to active records only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

}
