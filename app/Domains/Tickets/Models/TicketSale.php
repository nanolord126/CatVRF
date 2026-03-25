declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final /**
 * TicketSale
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TicketSale extends Model
{
    protected $table = 'ticket_sales';
    protected $fillable = [
        'tenant_id', 'event_id', 'buyer_id', 'organizer_id', 'quantity',
        'unit_price', 'subtotal', 'commission_amount', 'total_amount',
        'payment_status', 'sale_status', 'transaction_id',
        'paid_at', 'refunded_at', 'correlation_id'
    ];

    protected $casts = [
        'unit_price' => 'float',
        'subtotal' => 'float',
        'commission_amount' => 'float',
        'total_amount' => 'float',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
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

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'organizer_id');
    }
}
