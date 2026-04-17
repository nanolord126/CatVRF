<?php declare(strict_types=1);

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\RealEstate\Enums\BookingStatus;
use Modules\RealEstate\Enums\PropertyType;

final class PropertyBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_bookings';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'property_id',
        'user_id',
        'viewing_slot',
        'amount',
        'status',
        'deal_score',
        'fraud_score',
        'idempotency_key',
        'is_b2b',
        'hold_until',
        'face_id_verified',
        'blockchain_verified',
        'webrtc_room_id',
        'original_price',
        'dynamic_discount',
        'escrow_amount',
        'commission_split',
        'metadata',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'viewing_slot' => 'datetime',
        'hold_until' => 'datetime',
        'deal_score' => 'array',
        'fraud_score' => 'decimal:4',
        'amount' => 'decimal:14',
        'original_price' => 'decimal:14',
        'dynamic_discount' => 'decimal:14',
        'escrow_amount' => 'decimal:14',
        'commission_split' => 'array',
        'face_id_verified' => 'boolean',
        'blockchain_verified' => 'boolean',
        'is_b2b' => 'boolean',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', BookingStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', BookingStatus::CONFIRMED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', BookingStatus::COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', BookingStatus::CANCELLED);
    }

    public function scopeB2B($query)
    {
        return $query->where('is_b2b', true);
    }

    public function scopeB2C($query)
    {
        return $query->where('is_b2b', false);
    }

    public function scopeActiveHold($query)
    {
        return $query->where('hold_until', '>', now());
    }

    public function isHoldExpired(): bool
    {
        return $this->hold_until->isPast();
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === BookingStatus::PENDING && !$this->isHoldExpired();
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [BookingStatus::COMPLETED, BookingStatus::CANCELLED], true);
    }

    public function markAsConfirmed(array $blockchainData = []): self
    {
        $this->update([
            'status' => BookingStatus::CONFIRMED,
            'blockchain_verified' => true,
            'metadata' => array_merge($this->metadata ?? [], ['blockchain_data' => $blockchainData]),
        ]);
        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update(['status' => BookingStatus::COMPLETED]);
        return $this;
    }

    public function markAsCancelled(string $reason): self
    {
        $this->update([
            'status' => BookingStatus::CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], ['cancellation_reason' => $reason]),
        ]);
        return $this;
    }

    public function getDealScoreAttribute($value): array
    {
        return $value ? json_decode($value, true) : [
            'overall' => 0.0,
            'credit' => 0.0,
            'legal' => 0.0,
            'liquidity' => 0.0,
            'recommended' => false,
        ];
    }

    public function setDealScoreAttribute(array $value): void
    {
        $this->attributes['deal_score'] = json_encode($value);
    }
}
