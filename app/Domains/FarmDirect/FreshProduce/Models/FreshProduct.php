<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreshProduct extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'fresh_products';

        protected $fillable = [
            'tenant_id',
            'business_group_id',
            'farm_supplier_id',
            'uuid',
            'correlation_id',
            'name',
            'description',
            'category',
            'unit',
            'price_per_unit',
            'current_stock',
            'min_stock_threshold',
            'is_seasonal',
            'season_months',
            'is_eco_certified',
            'eco_certificate_number',
            'harvest_date',
            'expiry_days',
            'status',
            'tags',
            'meta',
        ];

        protected $hidden = [];

        protected $casts = [
            'price_per_unit'      => 'integer',
            'current_stock'       => 'integer',
            'min_stock_threshold' => 'integer',
            'expiry_days'         => 'integer',
            'is_seasonal'         => 'boolean',
            'is_eco_certified'    => 'boolean',
            'season_months'       => 'array',
            'tags'                => 'array',
            'meta'                => 'array',
            'harvest_date'        => 'date',
        ];

        public function farmSupplier(): BelongsTo
        {
            return $this->belongsTo(FarmSupplier::class, 'farm_supplier_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }
}
