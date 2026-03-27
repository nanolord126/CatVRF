<?php declare(strict_types=1);
namesp

/**
 * TutoringSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TutoringSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Education\Tutoring\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ace App\Domains\Education\Tutoring\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TutoringSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='tutoring_sessions';protected $fillable=['uuid','tenant_id','tutor_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','session_date','duration_hours','subject','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tutoring_sessions.tenant_id',tenant()->id));}}
