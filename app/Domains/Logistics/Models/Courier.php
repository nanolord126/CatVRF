<?php declare(strict_types=1);

names

/**
 * Courier
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Courier();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Logistics\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Courier extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'couriers';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'user_id', 'phone', 'vehicle', 'status', 'rating', 'tags', 'meta'
    ];
    protected $casts = [
        'rating' => 'float',
        'tags' => 'json',
        'meta' => 'json',
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
