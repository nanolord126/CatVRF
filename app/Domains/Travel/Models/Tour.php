<?php declare(strict_types=1);
name

/**
 * Tour
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Tour();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Travel\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
space App\Domains\Travel\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Tour extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='tours';protected $fillable=['uuid','tenant_id','agency_id','correlation_id','name','destination','price_kopecks','duration_days','rating','is_verified','tags'];protected $casts=['price_kopecks'=>'integer','duration_days'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tours.tenant_id',tenant()->id));}}
