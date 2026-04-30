<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Auto Service Order — Заказ на обслуживание в вертикали Автосервис
 * 
 * Расширяет базовую Deal модель специфичными полями для автосервисов.
 */
final class AutoServiceOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'vehicle_id',
        'service_type',
        'service_category',
        'specific_services',
        'mechanic_id',
        'service_date',
        'service_time',
        'estimated_duration_hours',
        'actual_duration_hours',
        'status',
        'check_in_time',
        'start_time',
        'end_time',
        'mileage',
        'fuel_level',
        'vehicle_condition_notes',
        'diagnosis',
        'work_performed',
        'parts_used',
        'parts_cost',
        'labor_hours',
        'labor_rate',
        'labor_cost',
        'additional_services',
        'additional_cost',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'total_amount',
        'payment_status',
        'payment_method',
        'warranty_days',
        'warranty_km',
        'follow_up_date',
        'follow_up_notes',
        'before_photos',
        'after_photos',
        'customer_signature',
        'mechanic_signature',
        'reminder_sent',
        'no_show',
        'cancellation_reason',
        'notes',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'service_date' => 'datetime',
        'service_time' => 'datetime',
        'check_in_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'follow_up_date' => 'datetime',
        'estimated_duration_hours' => 'decimal:2',
        'actual_duration_hours' => 'decimal:2',
        'mileage' => 'integer',
        'fuel_level' => 'integer',
        'diagnosis' => 'encrypted',
        'work_performed' => 'encrypted',
        'parts_used' => 'json',
        'parts_cost' => 'decimal:2',
        'labor_hours' => 'decimal:2',
        'labor_rate' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'additional_services' => 'json',
        'additional_cost' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'warranty_days' => 'integer',
        'warranty_km' => 'integer',
        'reminder_sent' => 'boolean',
        'no_show' => 'boolean',
        'before_photos' => 'json',
        'after_photos' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_auto_service_orders';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByMechanic($query, int $mechanicId)
    {
        return $query->where('mechanic_id', $mechanicId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('service_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('service_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('service_date', '>=', now())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('service_category', $category);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('service_category', 'maintenance');
    }

    public function scopeRepair($query)
    {
        return $query->where('service_category', 'repair');
    }

    public function scopeDiagnostic($query)
    {
        return $query->where('service_category', 'diagnostic');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        $this->status = 'confirmed';
        return $this->save();
    }

    public function checkIn(int $mileage, int $fuelLevel): bool
    {
        $this->status = 'checked_in';
        $this->check_in_time = now();
        $this->mileage = $mileage;
        $this->fuel_level = $fuelLevel;
        return $this->save();
    }

    public function start(): bool
    {
        $this->status = 'in_progress';
        $this->start_time = now();
        return $this->save();
    }

    public function complete(array $data = []): bool
    {
        $this->status = 'completed';
        $this->end_time = now();

        if (isset($data['diagnosis'])) {
            $this->diagnosis = $data['diagnosis'];
        }
        if (isset($data['work_performed'])) {
            $this->work_performed = $data['work_performed'];
        }
        if (isset($data['parts_used'])) {
            $this->parts_used = $data['parts_used'];
        }
        if (isset($data['parts_cost'])) {
            $this->parts_cost = $data['parts_cost'];
        }
        if (isset($data['labor_hours'])) {
            $this->labor_hours = $data['labor_hours'];
        }
        if (isset($data['actual_duration_hours'])) {
            $this->actual_duration_hours = $data['actual_duration_hours'];
        }
        if (isset($data['after_photos'])) {
            $this->after_photos = $data['after_photos'];
        }
        if (isset($data['follow_up_date'])) {
            $this->follow_up_date = $data['follow_up_date'];
        }

        // Автоматический расчет labor_cost
        if ($this->labor_hours > 0 && $this->labor_rate > 0) {
            $this->labor_cost = $this->labor_hours * $this->labor_rate;
        }

        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';
        $this->no_show = true;
        return $this->save();
    }

    public function calculateTotal(): float
    {
        $subtotal = ($this->parts_cost ?? 0) + ($this->labor_cost ?? 0) + ($this->additional_cost ?? 0);
        $discount = $subtotal * ($this->discount_percent / 100);
        return $subtotal - $discount - $this->discount_amount + $this->tax_amount;
    }

    public function getActualDuration(): ?float
    {
        if ($this->start_time === null || $this->end_time === null) {
            return $this->actual_duration_hours;
        }

        return $this->start_time->diffInHours($this->end_time);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет total_amount
            if ($model->total_amount === 0) {
                $model->total_amount = $model->calculateTotal();
            }
        });
    }
}
