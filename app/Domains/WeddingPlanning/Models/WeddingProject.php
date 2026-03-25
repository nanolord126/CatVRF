<?php declare(strict_types=1);
namespace App

/**
 * WeddingProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new WeddingProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\WeddingPlanning\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
\Domains\WeddingPlanning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WeddingProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='wedding_projects';protected $fillable=['uuid','tenant_id','planner_id','couple_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','wedding_date','guest_count','venue_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','wedding_date'=>'datetime','guest_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('wedding_projects.tenant_id',tenant()->id));}}
