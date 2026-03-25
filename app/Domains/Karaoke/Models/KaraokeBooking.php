<?php declare(strict_types=1);
names

/**
 * KaraokeBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new KaraokeBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Karaoke\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Karaoke\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class KaraokeBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='karaoke_bookings';protected $fillable=['uuid','tenant_id','club_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','room_number','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','room_number'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('karaoke_bookings.tenant_id',tenant()->id));}}
