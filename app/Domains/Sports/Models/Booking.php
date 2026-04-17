<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sports_bookings';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'user_id',
        'venue_id',
        'trainer_id',
        'slot_start',
        'slot_end',
        'booking_type',
        'status',
        'biometric_hash',
        'biometric_data',
        'payment_transaction_id',
        'amount',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'biometric_data' => 'json',
        'slot_start' => 'datetime',
        'slot_end' => 'datetime',
        'amount' => 'decimal:2',
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
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'venue_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    public function scopeForTrainer($query, int $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'checked_in']);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) 
            && $this->slot_start->gt(now()->addHours(2));
    }
}
