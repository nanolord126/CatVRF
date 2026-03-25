<?php declare(strict_types=1);
namespace 

/**
 * ServiceBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ServiceBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\HomeServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
App\Domains\HomeServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ServiceBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='service_bookings';protected $fillable=['uuid','tenant_id','provider_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','service_date','duration_hours','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','service_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('service_bookings.tenant_id',tenant()->id));}}
