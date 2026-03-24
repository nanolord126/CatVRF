<?php declare(strict_types=1);
namespace App\Domains\Fitness\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class FitnessMembership extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='fitness_memberships';protected $fillable=['uuid','tenant_id','gym_id','member_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','membership_type','start_date','end_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','start_date'=>'datetime','end_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('fitness_memberships.tenant_id',tenant()->id));}}
