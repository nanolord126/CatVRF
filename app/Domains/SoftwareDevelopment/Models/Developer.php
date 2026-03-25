<?php declare(strict_types=1);
namespace App\Dom

/**
 * Developer
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Developer();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\SoftwareDevelopment\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ains\SoftwareDevelopment\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Developer extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='developers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','technologies','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['technologies'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('developers.tenant_id',tenant()->id));}}
