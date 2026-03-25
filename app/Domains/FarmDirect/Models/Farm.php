<?php declare(strict_types=1);

namesp

/**
 * Farm
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Farm();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\FarmDirect\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ace App\Domains\FarmDirect\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Database\Factories\FarmFactory;

final class Farm extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = "farms";
    protected $guarded = [];
    protected $casts = [
        "tags"            => "json",
    ];

    protected static function newFactory()
    {
        return FarmFactory::new();
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

