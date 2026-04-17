<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SlotBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_slot_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'slot_id',
        'booking_reference',
        'status',
        'booked_at',
        'confirmed_at',
        'cancelled_at',
        'attended_at',
        'biometric_hash',
        'device_fingerprint',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'attended_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) 
            && $this->slot->start_time->gt(now()->addHours(2));
    }

    public function markAsAttended(): void
    {
        $this->update([
            'status' => 'completed',
            'attended_at' => now(),
        ]);
    }

    public function markAsNoShow(): void
    {
        $this->update([
            'status' => 'no_show',
        ]);
    }
}
