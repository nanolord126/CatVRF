<?php

declare(strict_types=1);

/**
 * DesignProject — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/designproject
 */

namespace App\Domains\Furniture\InteriorDesign\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DesignProject extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'design_projects';

    protected $fillable = ['uuid', 'tenant_id', 'designer_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'style', 'space_sqm', 'due_date', 'tags'];

    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'space_sqm' => 'integer', 'due_date' => 'datetime', 'tags' => 'json'];

    /**
     * Связь с дизайнером проекта.
     */
    public function designer(): BelongsTo
    {
        return $this->belongsTo(InteriorDesigner::class, 'designer_id');
    }

    /**
     * Проверяет, оплачен ли проект полностью.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Проверяет, просрочен ли дедлайн.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status !== 'completed';
    }

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
            'id' => $this->id ?? null,
            'status' => $this->status ?? null,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('design_projects.tenant_id', tenant()->id));
    }
}
