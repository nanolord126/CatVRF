<?php declare(strict_types=1);
namespace App\Domains\StrategicPlanning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class StrategyProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='strategy_projects';protected $fillable=['uuid','tenant_id','planner_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('strategy_projects.tenant_id',tenant()->id));}}
