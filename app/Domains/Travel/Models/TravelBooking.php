<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TravelBooking extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'travel_bookings';

    protected $fillable = [
        'tenant_id',
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

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant()->id);
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
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TravelReview::class, 'booking_id');
    }
}
