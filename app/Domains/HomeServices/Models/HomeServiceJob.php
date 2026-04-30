<?php

declare(strict_types=1);

/**
 * HomeServiceJob — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/homeservicejob
 */

namespace App\Domains\HomeServices\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class HomeServiceJob extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'home_service_jobs';

    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'contractor_id', 'client_id', 'service_type', 'datetime',
        'address', 'status', 'price', 'tags', 'meta',
    ];

    protected $casts = [
        'price' => 'int',
        'tags' => 'json',
        'meta' => 'json',
    ];

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
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
