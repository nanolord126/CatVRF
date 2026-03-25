<?php declare(strict_types=1);
namespac

/**
 * SittingBooking
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new SittingBooking();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\PetSitting\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\PetSitting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SittingBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='sitting_bookings';protected $fillable=['uuid','tenant_id','sitter_id','owner_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','start_date','end_date','pet_names','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','start_date'=>'datetime','end_date'=>'datetime','pet_names'=>'json','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('sitting_bookings.tenant_id',tenant()->id));}}
