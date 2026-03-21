<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

final class TicketType extends Model
{
    protected $table = 'ticket_types';
    protected $fillable = [
        'tenant_id', 'event_id', 'name', 'description', 'price',
        'total_quantity', 'sold_quantity', 'reserved_quantity',
        'sale_starts_at', 'sale_ends_at', 'max_per_buyer',
        'is_active', 'restrictions', 'correlation_id'
    ];

    protected $casts = [
        'price' => 'float',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'restrictions' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getAvailableCount(): int
    {
        return $this->total_quantity - $this->sold_quantity - $this->reserved_quantity;
    }
}
