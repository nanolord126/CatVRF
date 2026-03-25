declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne};
use Illuminate\Database\Eloquent\Casts\AsCollection;

final /**
 * Ticket
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Ticket extends Model
{
    protected $table = 'tickets';
    protected $fillable = [
        'tenant_id', 'event_id', 'ticket_type_id', 'ticket_number',
        'status', 'buyer_id', 'sold_at', 'scanned_at', 'qr_code',
        'checkin_expires_at', 'metadata', 'correlation_id'
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'scanned_at' => 'datetime',
        'checkin_expires_at' => 'datetime',
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
        return $this->belongsTo($this->event->class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function checkin(): HasOne
    {
        return $this->hasOne(EventCheckin::class);
    }
}
