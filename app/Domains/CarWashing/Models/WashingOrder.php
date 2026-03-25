<?php declare(strict_types=1);
namespac

/**
 * WashingOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new WashingOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\CarWashing\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\CarWashing\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WashingOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='washing_orders';protected $fillable=['uuid','tenant_id','station_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','service_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('washing_orders.tenant_id',tenant()->id));}}
