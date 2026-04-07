<?php

declare(strict_types=1);

namespace App\Domains\Beauty\SpaWellness\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SpaBooking — Eloquent-модель бронирования СПА-услуги.
 *
 * Таблица: spa_bookings
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class SpaBooking extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'spa_bookings';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'spa_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'treatment_type',
        'duration_minutes',
        'booking_date',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_kopecks'    => 'integer',
        'payout_kopecks'   => 'integer',
        'duration_minutes' => 'integer',
        'booking_date'     => 'datetime',
        'tags'             => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('spa_bookings.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function spaCenter(): BelongsTo
    {
        return $this->belongsTo(SpaCenter::class, 'spa_id');
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

