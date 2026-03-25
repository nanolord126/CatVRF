<?php declare(strict_types=1);
namespace App

/**
 * Hotel
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Hotel();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\HotelManagement\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
\Domains\HotelManagement\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Hotel extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='hotels';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','address','room_types','price_kopecks_per_night','stars','is_verified','tags'];protected $casts=['room_types'=>'json','price_kopecks_per_night'=>'integer','stars'=>'integer','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('hotels.tenant_id',tenant()->id));}}
