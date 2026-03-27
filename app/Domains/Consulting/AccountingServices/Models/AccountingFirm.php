<?php declare(strict_types=1);
namespace App\Do

/**
 * AccountingFirm
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new AccountingFirm();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Consulting\AccountingServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
mains\AccountingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class AccountingFirm extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='accounting_firms';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','services','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['services'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('accounting_firms.tenant_id',tenant()->id));}}
