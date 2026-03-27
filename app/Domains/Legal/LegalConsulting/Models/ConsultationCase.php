<?php declare(strict_types=1);
namespace App

/**
 * ConsultationCase
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ConsultationCase();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Legal\LegalConsulting\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
\Domains\LegalConsulting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ConsultationCase extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='consultation_cases';protected $fillable=['uuid','tenant_id','firm_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','case_type','consultation_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','consultation_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('consultation_cases.tenant_id',tenant()->id));}}
