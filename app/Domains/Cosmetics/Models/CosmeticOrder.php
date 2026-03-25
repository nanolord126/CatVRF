<?php declare(strict_types=1);

names

/**
 * CosmeticOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CosmeticOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Cosmetics\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Cosmetics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CosmeticOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'cosmetic_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'product_id', 'user_id', 'quantity', 'total_price', 'status', 'meta'
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
    public function product()
    {
        return $this->belongsTo(CosmeticProduct::class);
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
