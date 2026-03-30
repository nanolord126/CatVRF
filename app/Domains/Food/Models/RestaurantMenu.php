<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RestaurantMenu extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;
        use SoftDeletes;

        protected $table = 'restaurant_menus';

        protected $fillable = [
            'tenant_id',
            'restaurant_id',
            'name',
            'description',
            'sort_order',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'collection',
            'is_active' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function restaurant(): BelongsTo
        {
            return $this->belongsTo(Restaurant::class);
        }

        public function dishes(): HasMany
        {
            return $this->hasMany(Dish::class);
        }
}
