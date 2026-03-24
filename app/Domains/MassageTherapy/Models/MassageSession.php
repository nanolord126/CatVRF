<?php declare(strict_types=1);
namespace App\Domains\MassageTherapy\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MassageSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='massage_sessions';protected $fillable=['uuid','tenant_id','therapist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','massage_type','duration_minutes','session_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','duration_minutes'=>'integer','session_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('massage_sessions.tenant_id',tenant()->id));}}
