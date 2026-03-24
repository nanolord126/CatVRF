<?php declare(strict_types=1);
namespace App\Domains\EventManagement\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EventCoordination extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_coordinations';protected $fillable=['uuid','tenant_id','coordinator_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','event_type','coordination_hours','event_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','coordination_hours'=>'integer','event_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_coordinations.tenant_id',tenant()->id));}}
