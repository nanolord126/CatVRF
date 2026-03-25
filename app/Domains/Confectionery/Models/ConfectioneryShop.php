<?php declare(strict_types=1);

namespace

/**
 * ConfectioneryShop
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ConfectioneryShop();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Confectionery\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\Confectionery\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class ConfectioneryShop extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'confectionery_shops';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'correlation_id',
        'name', 'owner_id', 'description', 'address', 'phone',
        'latitude', 'longitude', 'certification_number', 'is_verified',
        'commission_percent', 'max_daily_orders', 'min_order_amount',
        'delivery_time_minutes', 'schedule', 'tags',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'commission_percent' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'max_daily_orders' => 'integer',
        'min_order_amount' => 'integer',
        'delivery_time_minutes' => 'integer',
        'schedule' => 'json',
        'tags' => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function products() { return $this->hasMany(Cake::class, 'confectionery_shop_id'); }
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function orders() { return $this->hasMany(CakeOrder::class, 'confectionery_shop_id'); }
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function customDesigns() { return $this->hasMany(CustomCakeDesign::class, 'confectionery_shop_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('confectionery_shops.tenant_id', tenant()->id));
    }
}

