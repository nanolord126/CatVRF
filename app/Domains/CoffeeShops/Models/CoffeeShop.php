<?php declare(strict_types=1);

namespa

/**
 * CoffeeShop
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CoffeeShop();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\CoffeeShops\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\CoffeeShops\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CoffeeShop extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'coffee_shops';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'address', 'phone', 'latitude', 'longitude', 'is_verified', 'commission_percent', 'min_order', 'tags'];
    protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function drinks() { return $this->hasMany(CoffeeDrink::class, 'shop_id'); }
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function orders() { return $this->hasMany(CoffeeOrder::class, 'shop_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('coffee_shops.tenant_id', tenant()->id));
    }
}
