<?php declare(strict_types=1);

names

/**
 * MeatProduct
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MeatProduct();
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
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class MeatProduct extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'meat_products';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'animal_type', 'cut_type', 'unit',
        'price_per_unit', 'current_stock',
        'is_farm_raised', 'is_halal', 'has_vet_certificate',
        'vet_certificate_num', 'is_vacuum_packed', 'status', 'tags',
    ];
    protected $casts = [
        'price_per_unit'       => 'int',
        'current_stock'        => 'int',
        'is_farm_raised'       => 'boolean',
        'is_halal'             => 'boolean',
        'has_vet_certificate'  => 'boolean',
        'is_vacuum_packed'     => 'boolean',
        'tags'                 => 'json',
    ];
}
