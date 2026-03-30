<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FoodConsumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;
        use SoftDeletes;

        protected $table = 'food_consumables';

        protected $fillable = [
            'tenant_id',
            'restaurant_id',
            'name',
            'unit',
            'current_stock',
            'min_stock_threshold',
            'price',
            'used_in_dishes',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'collection',
            'used_in_dishes' => 'collection',
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
            'price' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function restaurant(): BelongsTo
        {
            return $this->belongsTo(Restaurant::class);
        }
}
