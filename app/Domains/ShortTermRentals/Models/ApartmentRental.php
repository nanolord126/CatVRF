<?php declare(strict_types=1);
namespace App\

/**
 * ApartmentRental
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ApartmentRental();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ShortTermRentals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\ShortTermRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ApartmentRental extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='apartment_rentals';protected $fillable=['uuid','tenant_id','apartment_id','guest_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','check_in','check_out','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','check_in'=>'datetime','check_out'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('apartment_rentals.tenant_id',tenant()->id));}}
