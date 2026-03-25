<?php declare(strict_types=1);
namespace App\

/**
 * TrainingSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TrainingSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\BusinessTraining\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\BusinessTraining\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TrainingSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='training_sessions';protected $fillable=['uuid','tenant_id','provider_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','training_type','training_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','training_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('training_sessions.tenant_id',tenant()->id));}}
