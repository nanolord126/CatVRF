<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MasterSchedule — расписание мастера на конкретный день.
 * slots — массив доступных временных слотов ['09:00', '10:00', ...].
 * blocked_hours — массив заблокированных часов ['13:00', '14:00'].
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $tenant_id
 * @property int    $master_id
 * @property string $date              (Y-m-d)
 * @property array  $slots             (все слоты дня)
 * @property array  $blocked_hours     (заблокированные слоты)
 * @property bool   $is_day_off
 * @property string $correlation_id
 */
final class MasterSchedule extends Model
{
    protected $table = 'master_schedules';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'date',
        'slots',
        'blocked_hours',
        'is_day_off',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'date'          => 'date',
        'slots'         => 'array',
        'blocked_hours' => 'array',
        'is_day_off'    => 'boolean',
        'tags'          => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', static function ($query): void {
            if ($tenantId = tenant()->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function availableSlots(): array
    {
        if ($this->is_day_off) {
            return [];
        }

        $blocked = $this->blocked_hours ?? [];

        return array_values(
            array_filter(
                $this->slots ?? [],
                static fn (string $slot): bool => ! in_array($slot, $blocked, true)
            )
        );
    }

    public function hasAvailableSlots(): bool
    {
        return count($this->availableSlots()) > 0;
    }

    public function blockSlot(string $time): void
    {
        $blocked = $this->blocked_hours ?? [];
        if (! in_array($time, $blocked, true)) {
            $blocked[] = $time;
            $this->update(['blocked_hours' => $blocked]);
        }
    }

    public function releaseSlot(string $time): void
    {
        $blocked = array_values(
            array_filter(
                $this->blocked_hours ?? [],
                static fn (string $t): bool => $t !== $time
            )
        );
        $this->update(['blocked_hours' => $blocked]);
    }

    public function isSlotAvailable(string $time): bool
    {
        return in_array($time, $this->availableSlots(), true);
    }
}