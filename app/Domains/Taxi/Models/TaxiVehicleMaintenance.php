<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiVehicleMaintenance extends Model
{
    use HasFactory;

    protected $table = 'taxi_vehicle_maintenance';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'vehicle_id',
        'fleet_id',
        'type',
        'description',
        'status',
        'scheduled_date',
        'completed_date',
        'cost_kopeki',
        'performed_by',
        'odometer_km',
        'next_maintenance_date',
        'next_maintenance_odometer_km',
        'documents',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'next_maintenance_date' => 'datetime',
        'cost_kopeki' => 'integer',
        'odometer_km' => 'integer',
        'next_maintenance_odometer_km' => 'integer',
        'documents' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Типы технического обслуживания.
     */
    public const TYPE_ROUTINE = 'routine';
    public const TYPE_REPAIR = 'repair';
    public const TYPE_INSPECTION = 'inspection';
    public const TYPE_DIAGNOSTIC = 'diagnostic';
    public const TYPE_TIRE_CHANGE = 'tire_change';
    public const TYPE_OIL_CHANGE = 'oil_change';
    public const TYPE_BRAKE_SERVICE = 'brake_service';
    public const TYPE_EMERGENCY = 'emergency';

    /**
     * Статусы обслуживания.
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_OVERDUE = 'overdue';

    protected static function booted(): void
    {
        static::creating(function (TaxiVehicleMaintenance $maintenance) {
            $maintenance->uuid = $maintenance->uuid ?? (string) Str::uuid();
            $maintenance->tenant_id = $maintenance->tenant_id ?? (tenant()->id ?? 1);
            $maintenance->status = $maintenance->status ?? self::STATUS_SCHEDULED;
            $maintenance->correlation_id = $maintenance->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, 'vehicle_id');
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(TaxiFleet::class, 'fleet_id');
    }

    /**
     * Получить стоимость в рублях.
     */
    public function getCostInRubles(): float
    {
        return $this->cost_kopeki / 100;
    }

    /**
     * Проверить, запланировано ли обслуживание.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Проверить, выполнено ли обслуживание.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, просрочено ли обслуживание.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->scheduled_date && $this->scheduled_date->isPast() && !$this->isCompleted());
    }

    /**
     * Пометить как выполненное.
     */
    public function markAsCompleted(int $odometerKm, ?int $costKopeki = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_date' => now(),
            'odometer_km' => $odometerKm,
            'cost_kopeki' => $costKopeki ?? $this->cost_kopeki,
        ]);
    }

    /**
     * Пометить как просроченное.
     */
    public function markAsOverdue(): void
    {
        $this->update(['status' => self::STATUS_OVERDUE]);
    }

    /**
     * Отменить обслуживание.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], ['cancellation_reason' => $reason]),
        ]);
    }
}
