<?php declare(strict_types=1);
namespace App\Domains\SuccessionPlanning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SuccessionPlan extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='succession_plans';protected $fillable=['uuid','tenant_id','planner_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','plan_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('succession_plans.tenant_id',tenant()->id));}}
