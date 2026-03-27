<?php declare(strict_types=1);
namespace

/**
 * KidsBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new KidsBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Education\KidsCenters\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\Education\KidsCenters\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class KidsBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='kids_bookings';protected $fillable=['uuid','tenant_id','center_id','parent_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','kids_count','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','kids_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('kids_bookings.tenant_id',tenant()->id));}}
