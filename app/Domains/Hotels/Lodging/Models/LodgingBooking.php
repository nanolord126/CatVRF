<?php declare(strict_types=1);
names

/**
 * LodgingBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new LodgingBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Hotels\Lodging\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Hotels\Lodging\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class LodgingBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='lodging_bookings';protected $fillable=['uuid','tenant_id','lodge_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','check_in','check_out','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','check_in'=>'date','check_out'=>'date','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('lodging_bookings.tenant_id',tenant()->id));}}
