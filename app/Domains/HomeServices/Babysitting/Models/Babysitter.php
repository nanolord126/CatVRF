<?php

declare(strict_types=1);

/**
 * Babysitter — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/babysitter
 */

namespace App\Domains\HomeServices\Babysitting\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class Babysitter extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'babysitters';

    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'correlation_id', 'name', 'experience_years', 'price_kopecks_per_hour', 'rating', 'is_verified', 'tags'];

    protected $casts = ['experience_years' => 'integer', 'price_kopecks_per_hour' => 'integer', 'rating' => 'float', 'is_verified' => 'boolean', 'tags' => 'json'];

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

    protected static function booted_disabled()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('babysitters.tenant_id', tenant()->id));
    }
}
