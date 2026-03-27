<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель поставщика/фермера — КАНОН 2026.
 */
final class FarmSupplier extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'farm_suppliers';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'description',
        'contact_name',
        'contact_phone',
        'contact_email',
        'address',
        'geo_lat',
        'geo_lng',
        'inn',
        'commission_rate',
        'is_verified',
        'is_eco_certified',
        'rating',
        'review_count',
        'status',
        'tags',
        'meta',
    ];

    protected $hidden = ['inn'];

    protected $casts = [
        'commission_rate'  => 'float',
        'rating'           => 'float',
        'review_count'     => 'integer',
        'is_verified'      => 'boolean',
        'is_eco_certified' => 'boolean',
        'tags'             => 'array',
        'meta'             => 'array',
        'geo_lat'          => 'float',
        'geo_lng'          => 'float',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(FreshProduct::class, 'farm_supplier_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProduceOrder::class, 'farm_supplier_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
