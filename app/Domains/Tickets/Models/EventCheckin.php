<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EventCheckin extends Model
{
    protected $table = 'event_checkins';
    protected $fillable = [
        'tenant_id', 'event_id', 'ticket_id', 'buyer_id',
        'checked_in_by', 'checked_in_at', 'metadata', 'correlation_id'
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
