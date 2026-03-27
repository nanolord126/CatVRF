<?php declare(strict_types=1);

namespace App\Dom

/**
 * MaterialOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MaterialOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ConstructionAndRepair\ConstructionAndRepair\ConstructionMaterials\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ains\ConstructionMaterials\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class MaterialOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'material_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'material_id', 'user_id', 'quantity', 'total_price',
        'status', 'delivery_address', 'tracking_number', 'meta'
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
    public function material()
    {
        return $this->belongsTo(ConstructionMaterial::class);
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
