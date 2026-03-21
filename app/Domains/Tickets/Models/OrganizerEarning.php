<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizerEarning extends Model
{
    protected $table = 'organizer_earnings';
    protected $fillable = [
        'tenant_id', 'organizer_id', 'event_id',
        'total_tickets_sold', 'total_revenue', 'platform_commission',
        'organizer_earnings', 'last_payout_at', 'next_payout_at', 'correlation_id'
    ];

    protected $casts = [
        'total_revenue' => 'float',
        'platform_commission' => 'float',
        'organizer_earnings' => 'float',
        'last_payout_at' => 'datetime',
        'next_payout_at' => 'datetime',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
