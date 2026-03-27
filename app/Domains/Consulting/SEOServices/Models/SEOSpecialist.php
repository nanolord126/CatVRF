<?php declare(strict_types=1);
namespace

/**
 * SEOSpecialist
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new SEOSpecialist();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Consulting\SEOServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\Consulting\SEOServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SEOSpecialist extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='seo_specialists';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specialties','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['specialties'=>'json','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('seo_specialists.tenant_id',tenant()->id));}}
