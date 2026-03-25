<?php declare(strict_types=1);
namespace Ap

/**
 * MovingOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MovingOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\MovingServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\MovingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MovingOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='moving_orders';protected $fillable=['uuid','tenant_id','company_id','customer_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','move_date','duration_hours','from_address','to_address','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','move_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('moving_orders.tenant_id',tenant()->id));}}
