<?php declare(strict_types=1);

namesp

/**
 * MealProvider
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MealProvider();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ReadyMeals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ace App\Domains\ReadyMeals\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class MealProvider extends Model
{
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
