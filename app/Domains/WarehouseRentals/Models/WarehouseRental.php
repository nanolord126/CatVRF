<?php declare(strict_types=1);
namespace App\

/**
 * WarehouseRental
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new WarehouseRental();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\WarehouseRentals\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\WarehouseRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WarehouseRental extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='warehouse_rentals';protected $fillable=['uuid','tenant_id','warehouse_id','tenant_business_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','lease_start','lease_end','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lease_start'=>'datetime','lease_end'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('warehouse_rentals.tenant_id',tenant()->id));}}
