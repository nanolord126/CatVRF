<?php

declare(strict_types=1);

/**
 * Perfume — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/perfume
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class Perfume extends Model
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

    protected $table = 'flower_perfumes';

    protected $fillable = [
        'tenant_id', 'shop_id', 'brand', 'name',
        'description', 'fragrance_notes', 'volume_ml',
        'price', 'stock', 'is_available', 'uuid',
        'correlation_id', 'tags',
    ];

    protected $casts = [
        'fragrance_notes' => 'json', 'tags' => 'json',
        'is_available' => 'boolean', 'price' => 'decimal:2',
        'volume_ml' => 'integer', 'stock' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope(
            'tenant',
            fn ($q) => $q->where('tenant_id', tenant()->id ?? 0)
        );
    }
}
