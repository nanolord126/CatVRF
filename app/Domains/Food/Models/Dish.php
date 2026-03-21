<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $menu_id
 * @property string $name
 * @property int $price
 * @property int $cooking_time_minutes
 */
final class Dish extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'dishes';

    protected $fillable = [
        'tenant_id',
        'menu_id',
        'name',
        'description',
        'price',
        'calories',
        'allergens',
        'cooking_time_minutes',
        'consumables_json',
        'image_url',
        'is_available',
        'order_count',
        'rating',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'allergens' => 'collection',
        'consumables_json' => 'collection',
        'is_available' => 'boolean',
        'rating' => 'float',
        'price' => 'integer',
        'calories' => 'integer',
        'cooking_time_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(RestaurantMenu::class);
    }
}
