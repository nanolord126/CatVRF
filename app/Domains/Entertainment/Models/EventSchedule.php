declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\EntertainmentEvent;
use App\Domains\Entertainment\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * EventSchedule
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'event_schedules';
    protected $fillable = ['tenant_id', 'entertainment_event_id', 'show_number', 'start_time', 'end_time', 'total_seats', 'available_seats', 'ticket_price', 'is_cancelled', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'ticket_price' => 'float',
        'is_cancelled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function entertainmentEvent(): BelongsTo
    {
        return $this->belongsTo(Entertainment$this->event->class, 'entertainment_event_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'event_schedule_id');
    }
}
