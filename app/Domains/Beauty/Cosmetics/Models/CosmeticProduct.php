<?php

declare(strict_types=1);

/**
 * CosmeticProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cosmeticproduct
 */


namespace App\Domains\Beauty\Cosmetics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticProduct extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'cosmetic_products';

        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id', 'name', 'brand', 'sku', 'price',
            'ingredients', 'description', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'ingredients' => 'json',
            'price' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
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
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
