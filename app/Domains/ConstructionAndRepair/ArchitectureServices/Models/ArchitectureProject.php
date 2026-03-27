<?php declare(strict_types=1);
namespace App\Doma

/**
 * ArchitectureProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ArchitectureProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\ConstructionAndRepair\ConstructionAndRepair\ArchitectureServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ins\ArchitectureServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ArchitectureProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='architecture_projects';protected $fillable=['uuid','tenant_id','architect_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','building_sqm','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','building_sqm'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('architecture_projects.tenant_id',tenant()->id));}}
