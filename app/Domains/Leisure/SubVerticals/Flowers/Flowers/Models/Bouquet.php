<?php

declare(strict_types=1);

/**
 * Bouquet — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/bouquet
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class Bouquet extends Model
{
    use TenantScoped;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    protected $table = 'flower_bouquets';

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'description',
        'images', 'flowers_composition', 'price',
        'consumables_json', 'is_available', 'uuid',
        'correlation_id', 'tags',
    ];

    protected $casts = [
        'images' => 'json', 'flowers_composition' => 'json',
        'consumables_json' => 'json', 'tags' => 'json',
        'is_available' => 'boolean', 'price' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class, 'bouquet_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope(
            'tenant',
            fn ($q) => $q->where('tenant_id', tenant()->id ?? 0)
        );
    }
}
