<?php declare(strict_types=1);
na

/**
 * BarOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BarOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Bars\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
mespace App\Domains\Bars\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BarOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bar_orders';protected $fillable=['uuid','tenant_id','bar_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','order_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','order_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bar_orders.tenant_id',tenant()->id));}}
