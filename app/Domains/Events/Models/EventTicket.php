<?php declare(strict_types=1);
namespace App\Domains\Events\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EventTicket extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_tickets';protected $fillable=['uuid','tenant_id','event_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','ticket_code','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_tickets.tenant_id',tenant()->id));}}
