<?php declare(strict_types=1);

namesp

/**
 * Meal
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Meal();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Food\ReadyMeals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ace App\Domains\Food\ReadyMeals\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Meal extends Model
{
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
