<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * MasterSchedule — рабочее время мастера.
 * 
 * Хранит расписание мастера: дни работы, часы, перерывы, отпуска.
 * Используется для расчёта доступных слотов для записи.
 */
final class MasterSchedule extends Model
{
    use TenantScoped;

    protected $table = 'beauty_master_schedules';

    protected $fillable = [
        'tenant_id',
        'master_id',
        'day_of_week', // 0-6 (Sunday-Saturday)
        'start_time', // HH:MM:SS
        'end_time', // HH:MM:SS
        'break_start_time', // HH:MM:SS (nullable)
        'break_end_time', // HH:MM:SS (nullable)
        'is_available', // boolean
        'notes', // vacation, sick leave, etc.
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'break_start_time' => 'datetime:H:i:s',
        'break_end_time' => 'datetime:H:i:s',
        'is_available' => 'boolean',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    /**
     * Проверить, работает ли мастер в указанное время
     */
    public function isWorkingAt(Carbon $datetime): bool
    {
        if (!$this->is_available) {
            return false;
        }

        $dayOfWeek = $datetime->dayOfWeek;
        if ($this->day_of_week !== $dayOfWeek) {
            return false;
        }

        $time = $datetime->format('H:i:s');
        $startTime = $this->start_time->format('H:i:s');
        $endTime = $this->end_time->format('H:i:s');

        if ($time < $startTime || $time >= $endTime) {
            return false;
        }

        // Check break time
        if ($this->break_start_time && $this->break_end_time) {
            $breakStart = $this->break_start_time->format('H:i:s');
            $breakEnd = $this->break_end_time->format('H:i:s');
            
            if ($time >= $breakStart && $time < $breakEnd) {
                return false;
            }
        }

        return true;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
