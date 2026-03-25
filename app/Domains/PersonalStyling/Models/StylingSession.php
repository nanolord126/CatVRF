<?php declare(strict_types=1);
namespace App

/**
 * StylingSession
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new StylingSession();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\PersonalStyling\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
\Domains\PersonalStyling\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class StylingSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='styling_sessions';protected $fillable=['uuid','tenant_id','stylist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','style_type','session_hours','session_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','session_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('styling_sessions.tenant_id',tenant()->id));}}
