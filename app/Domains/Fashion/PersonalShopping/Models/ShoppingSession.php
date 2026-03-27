<?php declare(strict_types=1);
namespace App\

/**
 * ShoppingSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ShoppingSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Fashion\PersonalShopping\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\PersonalShopping\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ShoppingSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='shopping_sessions';protected $fillable=['uuid','tenant_id','shopper_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','session_date','duration_hours','items_purchased','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_date'=>'datetime','duration_hours'=>'integer','items_purchased'=>'json','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('shopping_sessions.tenant_id',tenant()->id));}}
