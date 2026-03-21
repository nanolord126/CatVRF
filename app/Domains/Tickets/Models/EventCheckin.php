<?php declare(strict_types=1);


















}    ) {}        public string $correlationId = '',        public TicketSale $ticketSale,    public function __construct(    use Dispatchable, InteractsWithSockets, SerializesModels;{final class TicketSaleCreateduse Illuminate\Queue\SerializesModels;use Illuminate\Foundation\Events\Dispatchable;use Illuminate\Broadcasting\InteractsWithSockets;use App\Domains\Tickets\Models\TicketSale;namespace App\Domains\Tickets\Events;namespace App\Domains\Tickets\Models;

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
            $query->where('tenant_id', tenant('id'));
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
