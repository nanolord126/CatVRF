<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class Slot extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'education_slots';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'teacher_id',
        'course_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'duration_minutes',
        'capacity',
        'booked_count',
        'slot_type',
        'status',
        'meeting_link',
        'meeting_password',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(SlotBooking::class, 'slot_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('start_time', '>', CarbonImmutable::now())
            ->whereRaw('booked_count < capacity');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isFullyBooked(): bool
    {
        return $this->booked_count >= $this->capacity;
    }

    public function hasStarted(): bool
    {
        return $this->start_time->isPast();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && ! $this->isFullyBooked() && ! $this->hasStarted();
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }
}
