<?php declare(strict_types=1);
namespa

/**
 * RentalCar
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new RentalCar();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\CarRental\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\CarRental\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class RentalCar extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='rental_cars';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','make','model','year','license_plate','price_kopecks_per_day','status','rating','tags'];protected $casts=['price_kopecks_per_day'=>'integer','year'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('rental_cars.tenant_id',tenant()->id));}}
