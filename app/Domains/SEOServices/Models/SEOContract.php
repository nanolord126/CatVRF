<?php declare(strict_types=1);
namespace

/**
 * SEOContract
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new SEOContract();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\SEOServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\SEOServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SEOContract extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='seo_contracts';protected $fillable=['uuid','tenant_id','specialist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','contract_type','months_duration','start_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','months_duration'=>'integer','start_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('seo_contracts.tenant_id',tenant()->id));}}
