<?php declare(strict_types=1);
namespace App\D

/**
 * ExecutiveSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ExecutiveSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\PersonalDevelopment\ExecutiveCoaching\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
omains\ExecutiveCoaching\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ExecutiveSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='executive_sessions';protected $fillable=['uuid','tenant_id','coach_id','executive_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','focus_area','session_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('executive_sessions.tenant_id',tenant()->id));}}
