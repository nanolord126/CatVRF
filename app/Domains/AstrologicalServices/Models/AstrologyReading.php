<?php declare(strict_types=1);
namespace App\Doma

/**
 * AstrologyReading
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new AstrologyReading();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\AstrologicalServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ins\AstrologicalServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class AstrologyReading extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='astrology_readings';protected $fillable=['uuid','tenant_id','astrologer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','reading_type','reading_hours','reading_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','reading_hours'=>'integer','reading_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('astrology_readings.tenant_id',tenant()->id));}}
