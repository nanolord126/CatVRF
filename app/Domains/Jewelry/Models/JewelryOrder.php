<?php declare(strict_types=1);

nam

/**
 * JewelryOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new JewelryOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Jewelry\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
espace App\Domains\Jewelry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class JewelryOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'jewelry_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'item_id', 'user_id', 'quantity', 'total_price', 'status', 'meta'
    ];
    protected $casts = [
        'quantity' => 'int',
        'total_price' => 'int',
        'meta' => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function item()
    {
        return $this->belongsTo(JewelryItem::class);
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
