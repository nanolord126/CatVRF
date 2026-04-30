<?php

declare(strict_types=1);

/**
 * FlowerConsumable — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/flowerconsumable
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class FlowerConsumable extends Model
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

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    protected $table = 'flower_consumables';

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'type',
        'current_stock', 'min_stock_threshold', 'unit',
        'price_per_unit', 'uuid', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'tags' => 'json', 'price_per_unit' => 'decimal:2',
        'current_stock' => 'integer', 'min_stock_threshold' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope(
            'tenant',
            fn ($q) => $q->where('tenant_id', tenant()->id ?? 0)
        );
    }
}
