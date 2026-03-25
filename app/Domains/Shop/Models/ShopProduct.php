<?php

declare(strict_types=1);

/**
 * ShopProduct
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ShopProduct();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Shop\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */


namespace App\Domains\Shop\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ShopProduct extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'shop_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'sku',
        'category',
        'price_kopeks',
        'compare_at_price_kopeks',
        'attributes',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'attributes' => 'json',
        'tags' => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_kopeks / 100, 2, '.', ' ') . ' ₽';
    }
}
