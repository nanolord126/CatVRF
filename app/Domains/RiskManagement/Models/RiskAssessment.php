<?php declare(strict_types=1);
namespace App\Domains\RiskManagement\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class RiskAssessment extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='risk_assessments';protected $fillable=['uuid','tenant_id','analyst_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','assessment_type','analysis_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','analysis_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('risk_assessments.tenant_id',tenant()->id));}}
