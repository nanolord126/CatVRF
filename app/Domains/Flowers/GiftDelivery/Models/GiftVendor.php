<?php declare(strict_types=1);
namespace 

/**
 * GiftVendor
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new GiftVendor();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Flowers\GiftDelivery\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
App\Domains\Flowers\GiftDelivery\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GiftVendor extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='gift_vendors';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','categories','rating','is_verified','tags'];protected $casts=['categories'=>'json','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('gift_vendors.tenant_id',tenant()->id));}}
