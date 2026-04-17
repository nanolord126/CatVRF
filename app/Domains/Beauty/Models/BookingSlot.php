<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Domains\Common\Models\BaseDomainModel;
use App\Domains\Common\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BookingSlot extends BaseDomainModel
{
    use HasFactory, TenantAware;

    protected $table = 'beauty_booking_slots';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'salon_id',
        'master_id',
        'service_id',
        'customer_id',
        'order_id',
        'slot_date',
        'slot_time',
        'duration_minutes',
        'status',
        'held_at',
        'expires_at',
        'booked_at',
        'released_at',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'slot_time' => 'datetime',
        'held_at' => 'datetime',
        'expires_at' => 'datetime',
        'booked_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Order\Models\Order::class, 'order_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeHeld($query)
    {
        return $query->where('status', 'held');
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('slot_date', $date);
    }

    public function scopeForMaster($query, int $masterId)
    {
        return $query->where('master_id', $masterId);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'held')
            ->where('expires_at', '<', now());
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    public function isBooked(): bool
    {
        return $this->status === 'booked';
    }

    public function isExpired(): bool
    {
        return $this->status === 'held' && $this->expires_at->isPast();
    }

    public function getHoldDurationMinutes(): int
    {
        if (!$this->held_at || !$this->expires_at) {
            return 0;
        }

        return $this->held_at->diffInMinutes($this->expires_at);
    }
}
