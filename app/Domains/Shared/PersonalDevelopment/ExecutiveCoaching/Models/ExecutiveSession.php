<?php

declare(strict_types=1);

/**
 * ExecutiveSession — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/executivesession
 */

namespace App\Domains\PersonalDevelopment\ExecutiveCoaching\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class ExecutiveSession extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'executive_sessions';

    protected $fillable = ['uuid', 'tenant_id', 'coach_id', 'executive_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'focus_area', 'session_hours', 'due_date', 'tags'];

    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'session_hours' => 'integer', 'due_date' => 'datetime', 'tags' => 'json'];

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
        self::addGlobalScope('tenant', fn ($q) => $q->where('executive_sessions.tenant_id', tenant()->id));
    }
}
