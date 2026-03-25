<?php declare(strict_types=1);
namespace

/**
 * PetGroomer
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new PetGroomer();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\PetServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\PetServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PetGroomer extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='pet_groomers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specialization','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('pet_groomers.tenant_id',tenant()->id));}}
