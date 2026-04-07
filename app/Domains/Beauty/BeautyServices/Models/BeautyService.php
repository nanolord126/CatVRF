<?php

declare(strict_types=1);

namespace App\Domains\Beauty\BeautyServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BeautyService — Eloquent-модель записи на услугу в студии (appointment).
 *
 * Таблица: beauty_service_appointments
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class BeautyService extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'beauty_service_appointments';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'studio_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'service_type',
        'duration_minutes',
        'appointment_date',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_kopecks'    => 'integer',
        'payout_kopecks'   => 'integer',
        'duration_minutes' => 'integer',
        'appointment_date' => 'datetime',
        'tags'             => 'json',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('beauty_service_appointments.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function studio(): BelongsTo
    {
        return $this->belongsTo(BeautyStudio::class, 'studio_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'pending_payment');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'completed');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getTotalRubles(): float
    {
        return $this->total_kopecks / 100;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'completed';
    }

    public function isCancellable(): bool
    {
        return ! in_array($this->status, ['completed', 'cancelled'], true);
    }
}

