<?php declare(strict_types=1);
namespace App\

/**
 * StrategyProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new StrategyProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ContentMarketing\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Domains\ContentMarketing\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class StrategyProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='strategy_projects';protected $fillable=['uuid','tenant_id','strategist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','strategy_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','strategy_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('strategy_projects.tenant_id',tenant()->id));}}
