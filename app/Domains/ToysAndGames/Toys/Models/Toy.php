<?php declare(strict_types=1);



/**
 * Toy
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Toy();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ToysAndGames\ToysAndGames\Toys\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
namespace App\Domains\ToysAndGames\ToysAndGames\Toys\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Toy extends Model { use HasUuids, SoftDeletes, TenantScoped;
    protected $table = 'toys'; protected $fillable = ['uuid','tenant_id','seller_id','correlation_id','name','price_kopecks','age_from','age_to','stock','tags'];
    protected $casts = ['price_kopecks'=>'integer','age_from'=>'integer','age_to'=>'integer','stock'=>'integer','tags'=>'json'];
    protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('toys.tenant_id',tenant()->id));}
}
