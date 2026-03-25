<?php declare(strict_types=1);
namespa

/**
 * InsuranceCompany
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new InsuranceCompany();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Insurance\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\Insurance\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class InsuranceCompany extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='insurance_companies';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','products','rating','is_verified','tags'];protected $casts=['products'=>'json','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('insurance_companies.tenant_id',tenant()->id));}}
