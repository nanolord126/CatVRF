<?php declare(strict_types=1);
namespace App

/**
 * Writer
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Writer();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\WritingServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
\Domains\WritingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Writer extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='writers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','genres','price_kopecks_per_word','rating','is_verified','tags'];protected $casts=['genres'=>'json','price_kopecks_per_word'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('writers.tenant_id',tenant()->id));}}
