<?php declare(strict_types=1);
namespace App\Domains\ChangeManagement\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ChangeInitiative extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='change_initiatives';protected $fillable=['uuid','tenant_id','manager_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','initiative_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('change_initiatives.tenant_id',tenant()->id));}}
