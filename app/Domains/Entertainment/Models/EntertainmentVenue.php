<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\Entertainer;
use App\Domains\Entertainment\Models\EntertainmentEvent;
use App\Domains\Entertainment\Models\Booking;
use App\Domains\Entertainment\Models\EventReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EntertainmentVenue extends Model
{
    use SoftDeletes;

    protected $table = 'entertainment_venues';
    protected $fillable = ['tenant_id', 'business_group_id', 'name', 'description', 'address', 'geo_point', 'venue_type', 'amenities', 'schedule', 'seating_capacity', 'standard_ticket_price', 'premium_ticket_price', 'rating', 'review_count', 'is_verified', 'is_active', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'amenities' => 'collection',
        'schedule' => 'collection',
        'venue_type' => 'collection',
        'standard_ticket_price' => 'float',
        'premium_ticket_price' => 'float',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function entertainers(): HasMany
    {
        return $this->hasMany(Entertainer::class, 'venue_id');
    }

    public function entertainmentEvents(): HasMany
    {
        return $this->hasMany(Entertainment$this->event->class, 'venue_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'venue_id');
    }

    public function eventReviews(): HasMany
    {
        return $this->hasMany(EventReview::class, 'entertainment_event_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }
}
