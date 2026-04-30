<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;
use App\Models\User;

/**
 * Taxi Ride CRM — Поездка в вертикали Такси
 * 
 * Расширяет базовую Deal модель специфичными полями для такси.
 */
final class TaxiRideCrm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'driver_id',
        'vehicle_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'scheduled_pickup_time',
        'actual_pickup_time',
        'dropoff_time',
        'distance_km',
        'duration_minutes',
        'estimated_price',
        'actual_price',
        'surge_multiplier',
        'ride_status',
        'payment_method',
        'payment_status',
        'rating',
        'review',
        'cancellation_reason',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'scheduled_pickup_time' => 'datetime',
        'actual_pickup_time' => 'datetime',
        'dropoff_time' => 'datetime',
        'distance_km' => 'float',
        'duration_minutes' => 'integer',
        'estimated_price' => 'integer',
        'actual_price' => 'integer',
        'surge_multiplier' => 'float',
        'rating' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'crm_taxi_rides';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByDriver($query, int $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('ride_status', ['assigned', 'arriving', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('ride_status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('ride_status', 'cancelled');
    }

    // ========================
    // METHODS
    // ========================

    public function assignDriver(int $driverId): bool
    {
        $this->driver_id = $driverId;
        $this->ride_status = 'assigned';
        return $this->save();
    }

    public function startRide(): bool
    {
        $this->ride_status = 'in_progress';
        $this->actual_pickup_time = now();
        return $this->save();
    }

    public function completeRide(int $actualPrice, ?int $rating = null, ?string $review = null): bool
    {
        $this->ride_status = 'completed';
        $this->actual_price = $actualPrice;
        $this->dropoff_time = now();
        
        if ($rating) {
            $this->rating = $rating;
        }
        if ($review) {
            $this->review = $review;
        }
        
        return $this->save();
    }

    public function cancelRide(string $reason): bool
    {
        $this->ride_status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
