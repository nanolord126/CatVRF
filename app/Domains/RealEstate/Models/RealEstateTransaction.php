<?php declare(strict_types=1);
namespac

/**
 * RealEstateTransaction
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new RealEstateTransaction();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\RealEstate\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\RealEstate\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class RealEstateTransaction extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='real_estate_transactions';protected $fillable=['uuid','tenant_id','agent_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','property_address','transaction_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('real_estate_transactions.tenant_id',tenant()->id));}}
