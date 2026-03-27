<?php declare(strict_types=1);
names

/**
 * Lodge
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Lodge();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Hotels\Lodging\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Hotels\Lodging\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Lodge extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='lodges';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_night','rooms','is_verified','tags'];protected $casts=['price_kopecks_per_night'=>'integer','rooms'=>'integer','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('lodges.tenant_id',tenant()->id));}}
