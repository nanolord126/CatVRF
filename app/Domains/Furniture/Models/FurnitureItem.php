<?php declare(strict_types=1);

names

/**
 * FurnitureItem
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new FurnitureItem();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Furniture\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Furniture\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class FurnitureItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'furniture_items';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'description', 'category', 'material', 'style',
        'price', 'current_stock', 'dimensions', 'weight_kg',
        'assembly_required', 'assembly_price', 'photo_url', 'status', 'tags',
    ];
    protected $casts = [
        'price'             => 'int',
        'current_stock'     => 'int',
        'assembly_price'    => 'int',
        'weight_kg'         => 'float',
        'assembly_required' => 'boolean',
        'tags'              => 'json',
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