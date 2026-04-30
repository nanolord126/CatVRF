<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Restaurant\Enums\StaffRole;

final class StaffShift extends Model
{
    use HasFactory;

    protected $table = 'restaurant_staff_shifts';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'uuid',
        'user_id',
        'role',
        'status',
        'shift_date',
        'start_time',
        'end_time',
        'actual_start_time',
        'actual_end_time',
        'duration_minutes',
        'orders_handled',
        'tips_amount',
        'notes',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'role' => StaffRole::class,
        'shift_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'orders_handled' => 'integer',
        'tips_amount' => 'decimal:2',
        'metadata' => 'json',
    ];

    // ========================
    // RELATIONSHIPS
    // ========================

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByRole($query, StaffRole $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('shift_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('shift_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', today())
            ->where('status', 'scheduled');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ========================
    // METHODS
    // ========================

    public function start(): bool
    {
        $this->status = 'active';
        $this->actual_start_time = now();
        return $this->save();
    }

    public function end(): bool
    {
        $this->status = 'completed';
        $this->actual_end_time = now();
        $this->calculateDuration();
        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';
        return $this->save();
    }

    public function calculateDuration(): void
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            $this->duration_minutes = $this->actual_start_time->diffInMinutes($this->actual_end_time);
        }
    }

    public function addOrderHandled(): void
    {
        $this->orders_handled += 1;
        $this->save();
    }

    public function addTips(float $amount): void
    {
        $this->tips_amount += $amount;
        $this->save();
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });

        self::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
