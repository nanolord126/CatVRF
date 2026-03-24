<?php declare(strict_types=1);
namespace App\Domains\BeautyServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BeautyService extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='beauty_service_appointments';protected $fillable=['uuid','tenant_id','studio_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','service_type','duration_minutes','appointment_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','duration_minutes'=>'integer','appointment_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('beauty_service_appointments.tenant_id',tenant()->id));}}
