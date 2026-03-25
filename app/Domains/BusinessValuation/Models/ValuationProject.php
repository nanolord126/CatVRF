<?php declare(strict_types=1);
namespace App\D

/**
 * ValuationProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ValuationProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\BusinessValuation\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
omains\BusinessValuation\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ValuationProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='valuation_projects';protected $fillable=['uuid','tenant_id','expert_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','valuation_type','valuation_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','valuation_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('valuation_projects.tenant_id',tenant()->id));}}
