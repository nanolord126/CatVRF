<?php declare(strict_types=1);

namesp

/**
 * MealOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MealOrder();
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

final class MealOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'meal_orders';
    protected $fillable = ['uuid', 'tenant_id', 'provider_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'delivery_datetime', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'delivery_datetime' => 'datetime', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function provider() { return $this->belongsTo(MealProvider::class, 'provider_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('meal_orders.tenant_id', tenant()->id));
    }
}
