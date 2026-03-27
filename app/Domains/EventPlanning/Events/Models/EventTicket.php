<?php declare(strict_types=1);
name

/**
 * EventTicket
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new EventTicket();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\EventPlanning\Events\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
space App\Domains\EventPlanning\Events\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EventTicket extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_tickets';protected $fillable=['uuid','tenant_id','event_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','ticket_code','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_tickets.tenant_id',tenant()->id));}}
