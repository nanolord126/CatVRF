<?php declare(strict_types=1);
namespace App\Doma

/**
 * EntertainmentBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new EntertainmentBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Tickets\EntertainmentBooking\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ins\EntertainmentBooking\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EntertainmentBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='entertainment_bookings';protected $fillable=['uuid','tenant_id','entertainer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','entertainment_type','duration_hours','event_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','duration_hours'=>'integer','event_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('entertainment_bookings.tenant_id',tenant()->id));}}
