<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Tourism Booking Model
 * 
 * Production-ready model for Tourism vertical with killer features:
 * - AI-personalized tours with embeddings
 * - Real-time availability hold with biometric verification
 * - Dynamic pricing + flash packages
 * - Virtual 360° tours + AR viewing
 * - Instant video-call with guides
 * - B2C quick booking + B2B corporate tours/MICE
 * - ML-fraud detection for cancellations
 * - Wallet split payment + instant cashback
 * - CRM integration at every status
 * 
 * @package App\Domains\Travel\Models
 */
final class TourBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tourism_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'tour_id',
        'user_id',
        'person_count',
        'start_date',
        'end_date',
        'total_amount',
        'base_price',
        'dynamic_price',
        'discount_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'biometric_token',
        'biometric_verified',
        'hold_expires_at',
        'virtual_tour_viewed',
        'virtual_tour_viewed_at',
        'video_call_scheduled',
        'video_call_time',
        'video_call_link',
        'video_call_meeting_id',
        'video_call_join_url',
        'payment_method',
        'split_payment_enabled',
        'cashback_amount',
        'cancellation_reason',
        'refund_amount',
        'cancelled_at',
        'fraud_score',
        'confirmed_at',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'base_price' => 'decimal:2',
        'dynamic_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'biometric_verified' => 'boolean',
        'hold_expires_at' => 'datetime',
        'virtual_tour_viewed' => 'boolean',
        'virtual_tour_viewed_at' => 'datetime',
        'video_call_scheduled' => 'boolean',
        'video_call_time' => 'datetime',
        'split_payment_enabled' => 'boolean',
        'cashback_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'fraud_score' => 'decimal:4',
        'confirmed_at' => 'datetime',
        'tags' => 'json',
        'metadata' => 'json',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (TourBooking $model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = request()?->header('X-Correlation-ID', Str::uuid()->toString());
            }
        });

        static::addGlobalScope('tenant', function ($query) {
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;
            $query->where('tenant_id', $tenantId);
        });
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    /**
     * Check if booking is in held status.
     */
    public function isHeld(): bool
    {
        return $this->status === 'held' && $this->hold_expires_at && $this->hold_expires_at->isFuture();
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking is B2B.
     */
    public function isB2B(): bool
    {
        return $this->business_group_id !== null;
    }

    /**
     * Get the string representation of this instance.
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
