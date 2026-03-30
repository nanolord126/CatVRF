<?php declare(strict_types=1);

namespace App\Domains\Food\ReadyMeals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Meal extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'meals';
        protected $fillable = ['uuid', 'tenant_id', 'provider_id', 'correlation_id', 'name', 'price_kopecks', 'calories', 'is_kit', 'description', 'tags'];
        protected $casts = ['price_kopecks' => 'integer', 'calories' => 'integer', 'is_kit' => 'boolean', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function provider() { return $this->belongsTo(MealProvider::class, 'provider_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('meals.tenant_id', tenant()->id));
        }
}
