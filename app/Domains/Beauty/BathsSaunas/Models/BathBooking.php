<?php

declare(strict_types=1);

namespace App\Domains\Beauty\BathsSaunas\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BathBooking — Eloquent-модель бронирования бани/сауны.
 *
 * Таблица: bath_bookings
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class BathBooking extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'bath_bookings';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'bath_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'booking_date',
        'duration_hours',
        'bath_type',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_kopecks'  => 'integer',
        'payout_kopecks' => 'integer',
        'booking_date'   => 'datetime',
        'duration_hours' => 'integer',
        'tags'           => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('bath_bookings.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function bathhouse(): BelongsTo
    {
        return $this->belongsTo(Bathhouse::class, 'bath_id');
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}

