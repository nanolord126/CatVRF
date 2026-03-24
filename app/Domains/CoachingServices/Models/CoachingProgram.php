<?php declare(strict_types=1);
namespace App\Domains\CoachingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class CoachingProgram extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='coaching_programs';protected $fillable=['uuid','tenant_id','coach_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','program_type','coaching_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','coaching_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('coaching_programs.tenant_id',tenant()->id));}}
