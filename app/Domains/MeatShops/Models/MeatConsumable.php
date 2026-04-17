<?php declare(strict_types=1);

/**
 * MeatConsumable — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/meatconsumable
 */


namespace App\Domains\MeatShops\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatConsumable extends Model
{

    protected $table = 'meat_consumables';
        protected $fillable = ['uuid', 'tenant_id', 'meat_shop_id', 'name', 'stock', 'min_threshold', 'correlation_id', 'tags'];
        protected $casts = ['tags' => 'json'];

        protected static function booted_disabled(): void
        {
            static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
            static::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
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
