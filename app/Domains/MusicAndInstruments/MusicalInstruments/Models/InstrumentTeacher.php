<?php declare(strict_types=1);
namespace App\Do

/**
 * InstrumentTeacher
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new InstrumentTeacher();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\MusicAndInstruments\MusicAndInstruments\MusicalInstruments\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
mains\MusicalInstruments\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class InstrumentTeacher extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='instrument_teachers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','instruments','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['instruments'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('instrument_teachers.tenant_id',tenant()->id));}}
