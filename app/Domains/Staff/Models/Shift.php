<?php

declare(strict_types=1);

namespace App\Domains\Staff\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Shift extends Model
{
    use TenantScoped;

    protected $table = 'shift_schedules';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'employee_id',
        'shift_type',
        'start_time',
        'end_time',
        'status',
        'notes',
        'is_auto_generated',
        'cancellation_reason',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_auto_generated' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
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
