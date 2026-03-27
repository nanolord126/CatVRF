<?php declare(strict_types=1);
namespace App\D

/**
 * ContentCreator
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new ContentCreator();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Art\ContentProduction\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
omains\ContentProduction\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ContentCreator extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='content_creators';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','content_types','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['content_types'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('content_creators.tenant_id',tenant()->id));}}
