<?php declare(strict_types=1);
namespace

/**
 * SupportTicket
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new SupportTicket();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\TechSupport\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\TechSupport\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SupportTicket extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='support_tickets';protected $fillable=['uuid','tenant_id','specialist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','issue_type','support_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','support_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('support_tickets.tenant_id',tenant()->id));}}
