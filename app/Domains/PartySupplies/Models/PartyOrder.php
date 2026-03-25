<?php declare(strict_types=1);
namespace A

/**
 * PartyOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new PartyOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\PartySupplies\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pp\Domains\PartySupplies\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PartyOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='party_orders';protected $fillable=['uuid','tenant_id','vendor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','items','delivery_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','items'=>'json','delivery_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('party_orders.tenant_id',tenant()->id));}}
