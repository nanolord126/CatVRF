declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\EntertainmentVenue;
use App\Domains\Entertainment\Models\Entertainer;
use App\Domains\Entertainment\Models\EventSchedule;
use App\Domains\Entertainment\Models\EventReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * EntertainmentEvent
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EntertainmentEvent extends Model
{
    use SoftDeletes;

    protected $table = 'entertainment_events';
    protected $fillable = ['tenant_id', 'venue_id', 'entertainer_id', 'name', 'description', 'event_type', 'event_date_start', 'event_date_end', 'total_seats', 'available_seats', 'base_price', 'vip_price', 'tags', 'rating', 'review_count', 'status', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'tags' => 'collection',
        'event_date_start' => 'datetime',
        'event_date_end' => 'datetime',
        'base_price' => 'float',
        'vip_price' => 'float',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(EntertainmentVenue::class, 'venue_id');
    }

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class, 'entertainer_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class, 'entertainment_event_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EventReview::class, 'entertainment_event_id');
    }
}
