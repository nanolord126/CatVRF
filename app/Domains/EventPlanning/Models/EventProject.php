<?php declare(strict_types=1);
namespace A

/**
 * EventProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new EventProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\EventPlanning\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pp\Domains\EventPlanning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EventProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_projects';protected $fillable=['uuid','tenant_id','planner_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','event_type','event_date','guest_count','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','event_date'=>'datetime','guest_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_projects.tenant_id',tenant()->id));}}
