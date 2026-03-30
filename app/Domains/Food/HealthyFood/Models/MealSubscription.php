<?php declare(strict_types=1);

namespace App\Domains\Food\HealthyFood\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MealSubscription extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'meal_subscriptions';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'client_id', 'diet_plan_id',
            'uuid', 'correlation_id',
            'frequency', 'next_delivery_date', 'delivery_address',
            'price_per_delivery', 'status', 'paused_until', 'total_deliveries', 'tags',
        ];
        protected $casts = [
            'price_per_delivery'  => 'int',
            'total_deliveries'    => 'int',
            'next_delivery_date'  => 'date',
            'paused_until'        => 'date',
            'tags'                => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function dietPlan(): BelongsTo
        {
            return $this->belongsTo(DietPlan::class, 'diet_plan_id');
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
