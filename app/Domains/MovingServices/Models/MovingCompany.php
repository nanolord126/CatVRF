<?php declare(strict_types=1);
namespace Ap

/**
 * MovingCompany
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MovingCompany();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\MovingServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\MovingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MovingCompany extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='moving_companies';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','trucks_count','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['trucks_count'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('moving_companies.tenant_id',tenant()->id));}}
