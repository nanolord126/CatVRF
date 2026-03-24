<?php declare(strict_types=1);
namespace App\Domains\Insurance\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class InsurancePolicy extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='insurance_policies';protected $fillable=['uuid','tenant_id','company_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','policy_type','coverage_amount','duration_months','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','coverage_amount'=>'integer','duration_months'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('insurance_policies.tenant_id',tenant()->id));}}
