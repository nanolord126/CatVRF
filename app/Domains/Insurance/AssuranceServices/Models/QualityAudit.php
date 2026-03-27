<?php declare(strict_types=1);
namespace App\D

/**
 * QualityAudit
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new QualityAudit();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Insurance\AssuranceServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
omains\AssuranceServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class QualityAudit extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='quality_audits';protected $fillable=['uuid','tenant_id','auditor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','audit_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('quality_audits.tenant_id',tenant()->id));}}
