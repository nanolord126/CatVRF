<?php declare(strict_types=1);
namespace App\Do

/**
 * AccountingProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new AccountingProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Consulting\AccountingServices\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
mains\AccountingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class AccountingProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='accounting_projects';protected $fillable=['uuid','tenant_id','firm_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','hours_allocated','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_allocated'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('accounting_projects.tenant_id',tenant()->id));}}
