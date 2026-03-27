<?php declare(strict_types=1);
namespace

/**
 * PetGroomingSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new PetGroomingSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Pet\PetServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\Pet\PetServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PetGroomingSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='pet_grooming_sessions';protected $fillable=['uuid','tenant_id','groomer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','session_date','duration_hours','pet_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('pet_grooming_sessions.tenant_id',tenant()->id));}}
