<?php declare(strict_types=1);

namespace

/**
 * BakeryOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BakeryOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Confectionery\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\Confectionery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Database\Factories\BakeryOrderFactory;

final class BakeryOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = "bakery_orders";
    protected $guarded = [];

    protected static function newFactory()
    {
        return BakeryOrderFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}

