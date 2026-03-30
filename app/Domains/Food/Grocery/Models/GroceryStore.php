<?php declare(strict_types=1);

namespace App\Domains\Food\Grocery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroceryStore extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $fillable = [
            'tenant_id', 'name', 'address', 'geo_point',
            'store_type', 'cuisines', 'delivery_zones',
            'is_active', 'rating', 'uuid', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'cuisines' => 'json', 'delivery_zones' => 'json',
            'tags' => 'json', 'is_active' => 'boolean',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) =>
                $q->where('tenant_id', tenant()->id ?? 0)
            );
        }

        public function products(): HasMany
        {
            return $this->hasMany(GroceryProduct::class, 'store_id');
        }

        public function orders(): HasMany
        {
            return $this->hasMany(GroceryOrder::class, 'store_id');
        }
}
