<?php declare(strict_types=1);
namespace 

/**
 * DanceClass
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new DanceClass();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\DanceStudios\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
App\Domains\DanceStudios\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DanceClass extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='dance_classes';protected $fillable=['uuid','tenant_id','studio_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','class_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','class_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('dance_classes.tenant_id',tenant()->id));}}
