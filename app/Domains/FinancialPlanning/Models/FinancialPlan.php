<?php declare(strict_types=1);
namespace App\Domains\FinancialPlanning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class FinancialPlan extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='financial_plans';protected $fillable=['uuid','tenant_id','advisor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','plan_type','planning_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','planning_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('financial_plans.tenant_id',tenant()->id));}}
