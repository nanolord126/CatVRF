<?php declare(strict_types=1);
namespace A

/**
 * CoworkingSpace
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CoworkingSpace();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\OfficeRentals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pp\Domains\OfficeRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class CoworkingSpace extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='coworking_spaces';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','seats_count','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['seats_count'=>'integer','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('coworking_spaces.tenant_id',tenant()->id));}}
