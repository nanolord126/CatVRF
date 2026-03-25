<?php declare(strict_types=1);

name

/**
 * ToyOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ToyOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ToysKids\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
space App\Domains\ToysKids\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class ToyOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'toy_orders';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'product_id', 'client_id', 'quantity', 'gift_wrapping',
        'total_price', 'delivery_date', 'status', 'idempotency_key', 'tags',
    ];
    protected $casts = [
        'quantity'      => 'int',
        'total_price'   => 'int',
        'gift_wrapping' => 'boolean',
        'delivery_date' => 'datetime',
        'tags'          => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ToyProduct::class, 'product_id');
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
