<?php declare(strict_types=1);
namespace Ap

/**
 * BouquetOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BouquetOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Flowers\FlowerDelivery\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\FlowerDelivery\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BouquetOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bouquet_orders';protected $fillable=['uuid','tenant_id','shop_id','customer_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','bouquet_type','recipient_address','delivery_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','delivery_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bouquet_orders.tenant_id',tenant()->id));}}
