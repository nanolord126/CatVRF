<?php declare(strict_types=1);
namespace Ap

/**
 * MassageTherapist
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MassageTherapist();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\MassageTherapy\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\MassageTherapy\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MassageTherapist extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='massage_therapists';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','massage_types','price_kopecks_per_minute','rating','is_verified','tags'];protected $casts=['massage_types'=>'json','price_kopecks_per_minute'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('massage_therapists.tenant_id',tenant()->id));}}
