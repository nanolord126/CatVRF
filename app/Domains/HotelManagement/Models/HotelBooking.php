<?php declare(strict_types=1);
namespace App

/**
 * HotelBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new HotelBooking();
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
final class HotelBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='hotel_bookings';protected $fillable=['uuid','tenant_id','hotel_id','guest_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','room_type','check_in','check_out','nights_count','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','check_in'=>'datetime','check_out'=>'datetime','nights_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('hotel_bookings.tenant_id',tenant()->id));}}
