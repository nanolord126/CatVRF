<?php declare(strict_types=1);
namespace App\Domains\DataAnalytics\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class AnalyticsProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='analytics_projects';protected $fillable=['uuid','tenant_id','analyst_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','analysis_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','analysis_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('analytics_projects.tenant_id',tenant()->id));}}
