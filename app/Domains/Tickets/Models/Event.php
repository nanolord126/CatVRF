<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

final class Event extends Model
{
    use SoftDeletes;

    protected $table = 'events';
    protected $fillable = [
        'tenant_id', 'organizer_id', 'title', 'description', 'category',
        'status', 'starts_at', 'ends_at', 'venue_name', 'venue_address',
        'geo_point', 'amenities', 'ticket_price_from', 'ticket_price_to',
        'total_capacity', 'tickets_sold', 'rating', 'review_count',
        'banner_url', 'tags', 'metadata', 'correlation_id'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'amenities' => AsCollection::class,
        'tags' => AsCollection::class,
        'metadata' => 'json',
        'ticket_price_from' => 'float',
        'ticket_price_to' => 'float',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'organizer_id');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(TicketSale::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EventReview::class);
    }

    public function organizerEarnings(): HasMany
    {
        return $this->hasMany(OrganizerEarning::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(EventCheckin::class);
    }
}
