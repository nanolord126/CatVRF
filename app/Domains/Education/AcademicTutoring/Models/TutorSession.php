<?php declare(strict_types=1);
namespace App\

/**
 * TutorSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TutorSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Education\AcademicTutoring\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\AcademicTutoring\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TutorSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='tutor_sessions';protected $fillable=['uuid','tenant_id','tutor_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','subject','session_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tutor_sessions.tenant_id',tenant()->id));}}
