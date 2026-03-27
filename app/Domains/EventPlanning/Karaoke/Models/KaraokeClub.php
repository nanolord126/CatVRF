<?php declare(strict_types=1);
names

/**
 * KaraokeClub
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new KaraokeClub();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\EventPlanning\Karaoke\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\EventPlanning\Karaoke\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class KaraokeClub extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='karaoke_clubs';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_hour','rooms','rating','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','rooms'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('karaoke_clubs.tenant_id',tenant()->id));}}
