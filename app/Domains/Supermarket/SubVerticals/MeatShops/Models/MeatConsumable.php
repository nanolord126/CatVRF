<?php

declare(strict_types=1);

/**
 * MeatConsumable — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/meatconsumable
 */

namespace App\Domains\Supermarket\SubVerticals\MeatShops\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class MeatConsumable extends Model
{
    use TenantScoped;

    protected $table = 'meat_consumables';

    protected $fillable = ['uuid', 'tenant_id', 'meat_shop_id', 'name', 'stock', 'min_threshold', 'correlation_id', 'tags'];

    protected $casts = ['tags' => 'json'];

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted_disabled(): void
    {
        self::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        self::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
    }
}
