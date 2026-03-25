declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    protected $table = 'ticket_sales';
    protected $fillable = ['tenant_id', 'booking_id', 'ticket_number', 'seat_number', 'ticket_price', 'barcode', 'status', 'used_at', 'refunded_at', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'ticket_price' => 'float',
        'used_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
