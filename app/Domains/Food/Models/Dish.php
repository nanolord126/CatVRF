<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Dish extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
