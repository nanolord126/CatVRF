<?php declare(strict_types=1);
namespace 

/**
 * DanceStudio
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new DanceStudio();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\DanceStudios\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
App\Domains\DanceStudios\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DanceStudio extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='dance_studios';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','style','price_kopecks_per_class','rating','is_verified','tags'];protected $casts=['price_kopecks_per_class'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('dance_studios.tenant_id',tenant()->id));}}
