<?php declare(strict_types=1);



/**
 * AutoPartOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new AutoPartOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Auto\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class AutoPartOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'auto_part_orders';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'part_id', 'client_id', 'vin', 'quantity',
        'total_price', 'delivery_date', 'status', 'idempotency_key', 'tags',
    ];
    protected $casts = [
        'quantity'      => 'int',
        'total_price'   => 'int',
        'delivery_date' => 'datetime',
        'tags'          => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(AutoPartItem::class, 'part_id');
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
