<?php declare(strict_types=1);
namespace App\Domains\Dentistry\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DentalAppointment extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='dental_appointments';protected $fillable=['uuid','tenant_id','dentist_id','patient_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','appointment_date','duration_minutes','service_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','appointment_date'=>'datetime','duration_minutes'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('dental_appointments.tenant_id',tenant()->id));}}
