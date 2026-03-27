<?php

declare(strict_types=1);


namespace App\Models\Domains\Food;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * FoodOrder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FoodOrder extends Model
{
    use HasFactory;

    protected $table = 'food_orders';

    protected static function newFactory()
    {
        return \Database\Factories\FoodOrderFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'customer_id',
        'total_amount',
        'status',
        'items',
        'delivery_address',
    ];

    protected $casts = [
        'items' => 'array',
    ];

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
