<?php declare(strict_types=1);

namespa

/**
 * DietPlan
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new DietPlan();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\HealthyFood\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\HealthyFood\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class DietPlan extends Model
{
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
