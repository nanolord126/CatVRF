<?php declare(strict_types=1);
namespac

/**
 * HallBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new HallBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\EventHalls\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\EventHalls\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class HallBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='hall_bookings';protected $fillable=['uuid','tenant_id','hall_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','event_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('hall_bookings.tenant_id',tenant()->id));}}
