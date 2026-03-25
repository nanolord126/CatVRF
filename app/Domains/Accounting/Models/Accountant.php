<?php declare(strict_types=1);
namespac

/**
 * Accountant
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Accountant();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Accounting\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\Accounting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Accountant extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='accountants';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specialization','price_kopecks_per_service','rating','is_verified','tags'];protected $casts=['price_kopecks_per_service'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('accountants.tenant_id',tenant()->id));}}
