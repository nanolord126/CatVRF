<?php declare(strict_types=1);
namespace Ap

/**
 * InteriorDesigner
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new InteriorDesigner();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Furniture\InteriorDesign\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\InteriorDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class InteriorDesigner extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='interior_designers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','styles','price_kopecks_per_sqm','rating','is_verified','tags'];protected $casts=['styles'=>'json','price_kopecks_per_sqm'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('interior_designers.tenant_id',tenant()->id));}}
