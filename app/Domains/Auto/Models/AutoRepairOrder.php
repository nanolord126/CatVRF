<?php

declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: AutoRepairOrder.
 * Модель заказа на сервисное обслуживание и ремонт (СТО).
 */
final class AutoRepairOrder extends Model
{
    use SoftDeletes;

    protected $table = 'auto_repair_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'auto_vehicle_id',
        'client_id',
        'status',
        'client_complaint',
        'mechanic_report',
        'labor_cost_kopecks',
        'parts_cost_kopecks',
        'total_cost_kopecks',
        'parts_list_json',
        'ai_estimate_json',
        'planned_at',
        'started_at',
        'finished_at',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'auto_vehicle_id' => 'integer',
        'parts_list_json' => 'json',
        'ai_estimate_json' => 'json',
        'labor_cost_kopecks' => 'integer',
        'parts_cost_kopecks' => 'integer',
        'total_cost_kopecks' => 'integer',
        'planned_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * КАНОН 2026: Automatic ID & Tenant Scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (AutoRepairOrder $order) {
            $order->uuid = $order->uuid ?? (string) Str::uuid();
            $order->tenant_id = $order->tenant_id ?? (tenant('id') ?? 1);
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('auto_repair_orders.tenant_id', tenant('id') ?? 1);
        });
    }

    /**
     * Связь с транспортным средством.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(AutoVehicle::class, 'auto_vehicle_id');
    }

    /**
     * Статусы ремонта.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Рассчитать итоговую сумму.
     */
    public function recalculateTotal(): int
    {
        $this->total_cost_kopecks = $this->labor_cost_kopecks + $this->parts_cost_kopecks;
        return (int) $this->total_cost_kopecks;
    }
}
