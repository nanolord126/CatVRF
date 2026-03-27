<?php declare(strict_types=1);
namespace App\D

/**
 * PodcastProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new PodcastProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Art\PodcastProduction\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
omains\PodcastProduction\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PodcastProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='podcast_projects';protected $fillable=['uuid','tenant_id','producer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','production_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','production_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('podcast_projects.tenant_id',tenant()->id));}}
