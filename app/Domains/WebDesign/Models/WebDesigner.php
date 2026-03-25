<?php declare(strict_types=1);
namespa

/**
 * WebDesigner
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new WebDesigner();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\WebDesign\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\WebDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WebDesigner extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='web_designers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','base_price_kopecks','rating','is_verified','tags'];protected $casts=['specializations'=>'json','base_price_kopecks'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('web_designers.tenant_id',tenant()->id));}}
