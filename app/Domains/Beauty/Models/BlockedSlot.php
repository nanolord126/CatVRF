<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * BlockedSlot — заблокированные слоты мастера.
 * 
 * Используется для блокировки времени: уборка, обучение, технический перерыв,
 * отпуск, болезнь и т.д. Эти слоты недоступны для онлайн-записи.
 */
final class BlockedSlot extends Model
{
    use TenantScoped;

    protected $table = 'beauty_blocked_slots';

    protected $fillable = [
        'tenant_id',
        'master_id',
        'salon_id',
        'start_time',
        'end_time',
        'reason', // vacation, training, cleaning, sick_leave, other
        'notes',
        'is_recurring', // for regular blocks (e.g., weekly training)
        'recurrence_pattern', // weekly, monthly
        'recurrence_end_date',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
        'recurrence_end_date' => 'date',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Проверить, пересекается ли слот с указанным периодом
     */
    public function overlapsWith(Carbon $start, Carbon $end): bool
    {
        return ($start < $this->end_time) && ($end > $this->start_time);
    }

    /**
     * Проверить, активен ли блокированный слот в указанное время
     */
    public function isActiveAt(Carbon $datetime): bool
    {
        return $datetime >= $this->start_time && $datetime < $this->end_time;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
