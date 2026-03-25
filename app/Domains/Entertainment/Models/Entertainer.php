declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\EntertainmentEvent;
use App\Domains\Entertainment\Models\PerformerSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * Entertainer
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Entertainer extends Model
{
    use SoftDeletes;

    protected $table = 'entertainers';
    protected $fillable = ['tenant_id', 'user_id', 'venue_id', 'full_name', 'bio', 'specializations', 'experience', 'hourly_rate', 'certification_url', 'rating', 'review_count', 'event_count', 'is_verified', 'is_active', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'specializations' => 'collection',
        'experience' => 'collection',
        'hourly_rate' => 'float',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function entertainmentEvents(): HasMany
    {
        return $this->hasMany(Entertainment$this->event->class, 'entertainer_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PerformerSchedule::class, 'entertainer_id');
    }
}
