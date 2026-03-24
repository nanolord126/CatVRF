<?php declare(strict_types=1);
namespace App\Domains\ArchitectureServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ArchitectureProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='architecture_projects';protected $fillable=['uuid','tenant_id','architect_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','building_sqm','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','building_sqm'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('architecture_projects.tenant_id',tenant()->id));}}
