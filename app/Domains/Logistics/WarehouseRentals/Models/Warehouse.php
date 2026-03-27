<?php declare(strict_types=1);
namespace App\

/**
 * Warehouse
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Warehouse();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Logistics\WarehouseRentals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\WarehouseRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Warehouse extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='warehouses';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','area_sqm','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['area_sqm'=>'integer','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('warehouses.tenant_id',tenant()->id));}}
