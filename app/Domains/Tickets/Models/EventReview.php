declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * EventReview
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventReview extends Model
{
    use SoftDeletes;

    protected $table = 'event_reviews';
    protected $fillable = [
        'tenant_id', 'event_id', 'buyer_id', 'ticket_sale_id',
        'rating', 'title', 'content', 'categories',
        'helpful_count', 'unhelpful_count', 'verified_purchase',
        'published_at', 'correlation_id'
    ];

    protected $casts = [
        'rating' => 'integer',
        'categories' => 'json',
        'verified_purchase' => 'boolean',
        'published_at' => 'datetime',
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
        return $this->belongsTo(\App\Models\User::class);
    }

    public function ticketSale(): BelongsTo
    {
        return $this->belongsTo(TicketSale::class);
    }
}
