<?php declare(strict_types=1);

n

/**
 * GiftOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new GiftOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Gifts\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
amespace App\Domains\Gifts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class GiftOrder extends Model { use HasUuids, SoftDeletes, TenantScoped;
    protected $table = 'gift_orders'; protected $fillable = ['uuid','tenant_id','seller_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','items_json','tags'];
    protected $casts = ['total_kopecks'=>'integer','payout_kopecks'=>'integer','items_json'=>'json','tags'=>'json'];
    protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('gift_orders.tenant_id',tenant()->id));}
}
