<?php declare(strict_types=1);

names

/**
 * MeatOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MeatOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\MeatShops\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\MeatShops\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class MeatOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'meat_orders';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'product_id', 'client_id', 'weight_kg', 'unit_price',
        'total_price', 'delivery_date', 'status', 'idempotency_key', 'tags',
    ];
    protected $casts = [
        'weight_kg'    => 'float',
        'unit_price'   => 'int',
        'total_price'  => 'int',
        'delivery_date' => 'datetime',
        'tags'         => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MeatProduct::class, 'product_id');
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
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
