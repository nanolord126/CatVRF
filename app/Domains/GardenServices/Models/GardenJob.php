<?php declare(strict_types=1);
namespace App\Domains\GardenServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GardenJob extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='garden_jobs';protected $fillable=['uuid','tenant_id','professional_id','customer_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','job_date','duration_hours','job_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','job_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('garden_jobs.tenant_id',tenant()->id));}}
