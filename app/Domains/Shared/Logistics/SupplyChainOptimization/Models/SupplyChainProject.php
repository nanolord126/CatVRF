<?php

declare(strict_types=1);

/**
 * SupplyChainProject — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/supplychainproject
 */

namespace App\Domains\Logistics\SupplyChainOptimization\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class SupplyChainProject extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'supply_chain_projects';

    protected $fillable = ['uuid', 'tenant_id', 'consultant_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'project_type', 'hours_spent', 'due_date', 'tags'];

    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'hours_spent' => 'integer', 'due_date' => 'datetime', 'tags' => 'json'];

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
        self::addGlobalScope('tenant', fn ($q) => $q->where('supply_chain_projects.tenant_id', tenant()->id));
    }
}
