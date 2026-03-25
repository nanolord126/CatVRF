<?php declare(strict_types=1);
names

/**
 * GymClub
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new GymClub();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Fitness\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Fitness\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GymClub extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='gym_clubs';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','classes_available','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['classes_available'=>'json','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('gym_clubs.tenant_id',tenant()->id));}}
