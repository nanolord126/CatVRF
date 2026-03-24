<?php declare(strict_types=1);
namespace App\Domains\Veterinary\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class VeterinaryAppointment extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='veterinary_appointments';protected $fillable=['uuid','tenant_id','clinic_id','owner_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','pet_name','pet_type','appointment_date','service_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','appointment_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('veterinary_appointments.tenant_id',tenant()->id));}}
