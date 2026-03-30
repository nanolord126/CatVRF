<?php declare(strict_types=1);

namespace App\Domains\Food\HealthyFood\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DietPlan extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'diet_plans';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'client_id',
            'uuid', 'correlation_id',
            'name', 'diet_type', 'duration_days', 'daily_calories',
            'price_per_day', 'schedule', 'status', 'starts_at', 'ends_at', 'tags',
        ];
        protected $casts = [
            'duration_days'  => 'int',
            'daily_calories' => 'int',
            'price_per_day'  => 'int',
            'schedule'       => 'json',
            'tags'           => 'json',
            'starts_at'      => 'date',
            'ends_at'        => 'date',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function subscriptions(): HasMany
        {
            return $this->hasMany(MealSubscription::class, 'diet_plan_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
