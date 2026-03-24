<?php declare(strict_types=1);
namespace App\Domains\InsuranceServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class InsuranceConsultation extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='insurance_consultations';protected $fillable=['uuid','tenant_id','agent_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','consultation_type','consultation_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','consultation_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('insurance_consultations.tenant_id',tenant()->id));}}
