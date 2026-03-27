<?php declare(strict_types=1);
namespace App\Dom

/**
 * SoftwareProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new SoftwareProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Freelance\SoftwareDevelopment\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ains\SoftwareDevelopment\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SoftwareProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='software_projects';protected $fillable=['uuid','tenant_id','developer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','development_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','development_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('software_projects.tenant_id',tenant()->id));}}
