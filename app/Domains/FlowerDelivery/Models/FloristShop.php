<?php declare(strict_types=1);
namespace Ap

/**
 * FloristShop
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new FloristShop();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\FlowerDelivery\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\FlowerDelivery\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class FloristShop extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='florist_shops';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','flowers_available','price_kopecks_per_bouquet','rating','is_verified','tags'];protected $casts=['flowers_available'=>'json','price_kopecks_per_bouquet'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('florist_shops.tenant_id',tenant()->id));}}
