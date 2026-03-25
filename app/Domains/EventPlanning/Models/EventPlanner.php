<?php declare(strict_types=1);
namespace A

/**
 * EventPlanner
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new EventPlanner();
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
final class EventPlanner extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_planners';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','event_types','price_kopecks_per_event','rating','is_verified','tags'];protected $casts=['event_types'=>'json','price_kopecks_per_event'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_planners.tenant_id',tenant()->id));}}
