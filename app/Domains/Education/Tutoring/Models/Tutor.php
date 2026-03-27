<?php declare(strict_types=1);
namesp

/**
 * Tutor
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Tutor();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Education\Tutoring\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ace App\Domains\Education\Tutoring\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Tutor extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='tutors';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','subject','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tutors.tenant_id',tenant()->id));}}
