<?php declare(strict_types=1);
namespa

/**
 * BilliardBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BilliardBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Billiards\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\Billiards\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BilliardBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='billiard_bookings';protected $fillable=['uuid','tenant_id','hall_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','table_number','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','table_number'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('billiard_bookings.tenant_id',tenant()->id));}}
