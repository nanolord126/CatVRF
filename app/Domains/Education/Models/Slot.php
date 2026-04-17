<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Slot extends Model
{
    use HasFactory, SoftDeletes;

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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
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
            ->where('start_time', '>', now())
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
        return $this->status === 'available' && !$this->isFullyBooked() && !$this->hasStarted();
    }
}
