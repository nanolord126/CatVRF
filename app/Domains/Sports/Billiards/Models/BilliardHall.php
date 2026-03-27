<?php declare(strict_types=1);
namespa

/**
 * BilliardHall
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BilliardHall();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Sports\Billiards\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\Sports\Billiards\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BilliardHall extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='billiard_halls';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_hour','table_count','rating','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','table_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('billiard_halls.tenant_id',tenant()->id));}}
