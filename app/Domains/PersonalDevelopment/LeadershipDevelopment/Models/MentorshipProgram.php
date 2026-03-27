<?php declare(strict_types=1);
namespace App\Domai

/**
 * MentorshipProgram
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new MentorshipProgram();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\PersonalDevelopment\LeadershipDevelopment\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ns\LeadershipDevelopment\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MentorshipProgram extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='mentorship_programs';protected $fillable=['uuid','tenant_id','mentor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','program_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('mentorship_programs.tenant_id',tenant()->id));}}
