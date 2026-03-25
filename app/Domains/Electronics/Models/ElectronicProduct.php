<?php declare(strict_types=1);

namespa

/**
 * ElectronicProduct
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ElectronicProduct();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Electronics\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class ElectronicProduct extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'electronic_products';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'description', 'category', 'brand', 'sku',
        'price', 'current_stock', 'warranty_months',
        'specifications', 'photo_url', 'status', 'tags',
    ];
    protected $casts = [
        'price'           => 'int',
        'current_stock'   => 'int',
        'warranty_months' => 'int',
        'specifications'  => 'json',
        'tags'            => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class, 'product_id');
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