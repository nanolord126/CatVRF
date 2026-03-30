<?php declare(strict_types=1);

namespace App\Domains\Food\ReadyMeals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MealProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'meal_providers';
        protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'address', 'phone', 'latitude', 'longitude', 'is_verified', 'commission_percent', 'min_order', 'tags'];
        protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function meals() { return $this->hasMany(Meal::class, 'provider_id'); }
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function orders() { return $this->hasMany(MealOrder::class, 'provider_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('meal_providers.tenant_id', tenant()->id));
        }
}
