<?php declare(strict_types=1);
namespace A

/**
 * CoworkingRental
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CoworkingRental();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\RealEstate\OfficeRentals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pp\Domains\OfficeRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class CoworkingRental extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='coworking_rentals';protected $fillable=['uuid','tenant_id','space_id','tenant_business_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','lease_start','lease_end','seats_booked','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lease_start'=>'datetime','lease_end'=>'datetime','seats_booked'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('coworking_rentals.tenant_id',tenant()->id));}}
