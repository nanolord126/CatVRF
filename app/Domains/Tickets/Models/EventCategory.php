declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final /**
 * EventCategory
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventCategory extends Model
{
    protected $table = 'event_categories';
    protected $fillable = [
        'name', 'description', 'slug', 'icon_url',
        'sort_order', 'event_count', 'is_active', 'correlation_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'event_count' => 'integer',
    ];

    public function events(): HasMany
    {
        return $this->hasMany($this->event->class, 'category', 'slug');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
