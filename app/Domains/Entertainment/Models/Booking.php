<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\EntertainmentVenue;
use App\Domains\Entertainment\Models\EventSchedule;
use App\Domains\Entertainment\Models\TicketSale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Booking extends Model
{
    use SoftDeletes;

    protected $table = 'bookings';
    protected $fillable = ['tenant_id', 'venue_id', 'event_schedule_id', 'customer_id', 'number_of_seats', 'total_price', 'commission_amount', 'booking_date', 'status', 'cancellation_reason', 'transaction_id', 'cancelled_at', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'total_price' => 'float',
        'commission_amount' => 'float',
        'booking_date' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(EntertainmentVenue::class, 'venue_id');
    }

    public function eventSchedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class, 'event_schedule_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TicketSale::class, 'booking_id');
    }
}
