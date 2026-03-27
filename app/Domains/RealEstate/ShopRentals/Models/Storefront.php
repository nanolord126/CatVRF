<?php declare(strict_types=1);
namespace

/**
 * Storefront
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Storefront();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\RealEstate\ShopRentals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\RealEstate\ShopRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Storefront extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='storefronts';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','area_sqm','location','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['area_sqm'=>'integer','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('storefronts.tenant_id',tenant()->id));}}
