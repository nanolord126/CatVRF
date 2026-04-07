<?php

declare(strict_types=1);

namespace App\Domains\Beauty\MassageTherapy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MassageSession — Eloquent-модель сеанса массажа.
 *
 * Таблица: massage_sessions
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class MassageSession extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'massage_sessions';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'therapist_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'massage_type',
        'duration_minutes',
        'session_date',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_kopecks'    => 'integer',
        'payout_kopecks'   => 'integer',
        'duration_minutes' => 'integer',
        'session_date'     => 'datetime',
        'tags'             => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('massage_sessions.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(MassageTherapist::class, 'therapist_id');
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

