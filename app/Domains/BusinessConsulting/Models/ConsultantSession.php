<?php declare(strict_types=1);
namespace App\Domains\BusinessConsulting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ConsultantSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='consultant_sessions';protected $fillable=['uuid','tenant_id','consultant_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','session_date','duration_hours','topic','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('consultant_sessions.tenant_id',tenant()->id));}}
