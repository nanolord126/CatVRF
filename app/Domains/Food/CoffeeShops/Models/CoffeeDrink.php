<?php declare(strict_types=1);

namespace App\Domains\Food\CoffeeShops\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CoffeeDrink extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'coffee_drinks';
        protected $fillable = ['uuid', 'tenant_id', 'shop_id', 'correlation_id', 'name', 'price_kopecks', 'description', 'tags'];
        protected $casts = ['price_kopecks' => 'integer', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function shop() { return $this->belongsTo(CoffeeShop::class, 'shop_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('coffee_drinks.tenant_id', tenant()->id));
        }
}
