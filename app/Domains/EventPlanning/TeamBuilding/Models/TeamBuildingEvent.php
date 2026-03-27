<?php declare(strict_types=1);
namespace 

/**
 * TeamBuildingEvent
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TeamBuildingEvent();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\EventPlanning\TeamBuilding\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
App\Domains\EventPlanning\TeamBuilding\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TeamBuildingEvent extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='team_building_events';protected $fillable=['uuid','tenant_id','facilitator_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','event_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('team_building_events.tenant_id',tenant()->id));}}
