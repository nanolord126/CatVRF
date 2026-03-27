<?php declare(strict_types=1);

namespa

/**
 * CoffeeDrink
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CoffeeDrink();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Food\CoffeeShops\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\Food\CoffeeShops\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CoffeeDrink extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'coffee_drinks';
    protected $fillable = ['uuid', 'tenant_id', 'shop_id', 'correlation_id', 'name', 'price_kopecks', 'description', 'tags'];
    protected $casts = ['price_kopecks' => 'integer', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function shop() { return $this->belongsTo(CoffeeShop::class, 'shop_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('coffee_drinks.tenant_id', tenant()->id));
    }
}
