<?php

declare(strict_types=1);

namespace App\Domains\Auto\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AutoPart extends Model
{
    use TenantScoped;

    protected $table = 'auto_parts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'auto_catalog_brand_id',
        'sku',
        'gtin',
        'oem_number',
        'name',
        'description',
        'price_kopecks',
        'wholesale_price_kopecks',
        'stock_quantity',
        'category',
        'compatibility_vin',
        'correlation_id',
        'tags',
        'metadata',
        'min_threshold',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'auto_catalog_brand_id' => 'integer',
        'price_kopecks' => 'integer',
        'wholesale_price_kopecks' => 'integer',
        'stock_quantity' => 'integer',
        'min_threshold' => 'integer',
        'compatibility_vin' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Отношение к бренду (Производителю).
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(AutoCatalogBrand::class, 'auto_catalog_brand_id');
    }

    /**
     * Проверка низкого остатка.
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= ($this->min_threshold ?? 0);
    }

    /**
     * Валидация совместимости.
     */
    public function isCompatibleWithVin(string $vin): bool
    {
        if (empty($this->compatibility_vin)) {
            return true;
        }

        return in_array($vin, (array) $this->compatibility_vin, true);
    }

    /**
     * КАНОН 2026: Automatic ID & Tenant Scoping.
     */
    protected static function booted(): void
    {
        self::creating(function (AutoPart $part) {
            $part->uuid = $part->uuid ?? (string) Str::uuid();
            $part->tenant_id = $part->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('auto_parts.tenant_id', tenant()->id ?? 1);
        });
    }
}
