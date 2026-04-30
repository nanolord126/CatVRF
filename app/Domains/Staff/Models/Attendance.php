<?php

declare(strict_types=1);

namespace App\Domains\Staff\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Attendance extends Model
{
    use TenantScoped;

    protected $table = 'time_entries';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'employee_id',
        'shift_id',
        'clock_in',
        'clock_out',
        'duration_minutes',
        'status',
        'notes',
        'gps_latitude',
        'gps_longitude',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'duration_minutes' => 'integer',
        'gps_latitude' => 'decimal:10,7',
        'gps_longitude' => 'decimal:11,7',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'employee_id');
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('clock_in', $date);
    }

    // Helper aliases for service compatibility
    public function getCheckInTimeAttribute()
    {
        return $this->clock_in;
    }

    public function getCheckOutTimeAttribute()
    {
        return $this->clock_out;
    }

    public function getHoursWorkedAttribute()
    {
        return $this->duration_minutes / 60;
    }

    public function getCheckInLatitudeAttribute()
    {
        return $this->gps_latitude;
    }

    public function getCheckInLongitudeAttribute()
    {
        return $this->gps_longitude;
    }

    public function getDateAttribute()
    {
        return $this->clock_in ? $this->clock_in->toDateString() : null;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
