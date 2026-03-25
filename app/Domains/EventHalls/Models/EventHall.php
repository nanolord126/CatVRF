<?php declare(strict_types=1);
namespac

/**
 * EventHall
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new EventHall();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\EventHalls\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\EventHalls\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EventHall extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_halls';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','capacity','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['capacity'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_halls.tenant_id',tenant()->id));}}
