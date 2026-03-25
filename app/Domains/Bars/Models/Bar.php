<?php declare(strict_types=1);
na

/**
 * Bar
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Bar();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Bars\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
mespace App\Domains\Bars\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Bar extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bars';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_drink','min_age','rating','tags'];protected $casts=['price_kopecks_per_drink'=>'integer','min_age'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bars.tenant_id',tenant()->id));}}
