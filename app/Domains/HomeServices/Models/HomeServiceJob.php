<?php declare(strict_types=1);

namespac

/**
 * HomeServiceJob
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new HomeServiceJob();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\HomeServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\HomeServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class HomeServiceJob extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'home_service_jobs';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'contractor_id', 'client_id', 'service_type', 'datetime',
        'address', 'status', 'price', 'tags', 'meta'
    ];
    protected $casts = [
        'price' => 'int',
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
