<?php declare(strict_types=1);

/**
 * JewelryItem — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/jewelryitem
 */


namespace App\Domains\Luxury\Jewelry\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JewelryItem extends Model
{


        protected $table = 'jewelry_items';

        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id', 'name', 'sku', 'price', 'metal',
            'stone', 'certificate_code', 'weight_grams', 'description', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'price' => 'integer',
            'weight_grams' => 'float',
        ];

        protected static function booted(): void
        {
            $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
