<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель расписания работника.
 * Согласно КАНОН 2026: планирование смен, отслеживание присутствия, расчёт зарплаты.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string|null $uuid
 * @property \Carbon\Carbon $date Дата смены
 * @property string $start_time Время начала (формат HH:MM)
 * @property string $end_time Время окончания (формат HH:MM)
 * @property int $duration_minutes Длительность в минутах
 * @property string $shift_type (morning, afternoon, night, custom)
 * @property string $status (scheduled, confirmed, started, completed, cancelled, no_show)
 * @property \Carbon\Carbon|null $actual_start_time Фактическое время начала
 * @property \Carbon\Carbon|null $actual_end_time Фактическое время окончания
 * @property string|null $notes Примечания
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class StaffSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'staff_schedules';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'uuid',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'shift_type',
        'status',
        'actual_start_time',
        'actual_end_time',
        'notes',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'duration_minutes' => 'integer',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Типы смен.
     */
    public const SHIFT_TYPE_MORNING = 'morning';
    public const SHIFT_TYPE_AFTERNOON = 'afternoon';
    public const SHIFT_TYPE_NIGHT = 'night';
    public const SHIFT_TYPE_CUSTOM = 'custom';

    /**
     * Статусы смены.
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить пользователя.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Получить фактическую длительность смены.
     */
    public function getActualDuration(): int
    {
        if (!$this->actual_start_time || !$this->actual_end_time) {
            return 0;
        }

        return (int) $this->actual_end_time->diffInMinutes($this->actual_start_time);
    }

    /**
     * Отметить как началась смена.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_STARTED,
            'actual_start_time' => now(),
        ]);
    }

    /**
     * Отметить как завершена смена.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_end_time' => now(),
        ]);
    }

    /**
     * Отменить смену.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Помечить как не явился.
     */
    public function markAsNoShow(): void
    {
        $this->update(['status' => self::STATUS_NO_SHOW]);
    }

    /**
     * Проверить, в прошлом ли смена.
     */
    public function isPast(): bool
    {
        return $this->date->isPast();
    }

    /**
     * Проверить, сегодня ли смена.
     */
    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    /**
     * Проверить, в будущем ли смена.
     */
    public function isFuture(): bool
    {
        return $this->date->isFuture();
    }
}
