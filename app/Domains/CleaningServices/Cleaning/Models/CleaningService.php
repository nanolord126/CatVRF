<?php declare(strict_types=1);
namesp

/**
 * CleaningService
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CleaningService();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\CleaningServices\CleaningServices\Cleaning\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ace App\Domains\CleaningServices\CleaningServices\Cleaning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class CleaningService extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='cleaning_services';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','service_type','price_kopecks_per_hour','workers_count','rating','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','workers_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('cleaning_services.tenant_id',tenant()->id));}}
