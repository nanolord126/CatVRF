<?php declare(strict_types=1);
namespace A

/**
 * BrandProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BrandProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Consulting\BrandStrategy\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pp\Domains\BrandStrategy\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BrandProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='brand_projects';protected $fillable=['uuid','tenant_id','strategist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','consultation_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','consultation_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('brand_projects.tenant_id',tenant()->id));}}
