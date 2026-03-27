<?php declare(strict_types=1);
namespace Ap

/**
 * BeautyStudio
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BeautyStudio();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Beauty\BeautyServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\BeautyServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BeautyStudio extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='beauty_studios';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','address','services','price_kopecks_per_minute','rating','is_verified','tags'];protected $casts=['services'=>'json','price_kopecks_per_minute'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('beauty_studios.tenant_id',tenant()->id));}}
