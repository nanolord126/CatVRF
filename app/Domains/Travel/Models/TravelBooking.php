<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class TravelBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'travel_bookings';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'agency_id',
        'tour_id',
        'user_id',
        'booking_number',
        'participants_count',
        'price_per_person',
        'total_amount',
        'commission_amount',
        'participants_data',
        'status',
        'payment_status',
        'transaction_id',
        'booked_at',
        'cancelled_at',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'participants_data' => 'collection',
        'price_per_person' => 'float',
        'total_amount' => 'float',
        'commission_amount' => 'float',
        'participants_count' => 'integer',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id', 'transaction_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(TravelTour::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TravelReview::class, 'booking_id');
    }
}
