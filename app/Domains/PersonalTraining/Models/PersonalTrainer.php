<?php declare(strict_types=1);
namespace App\Domains\PersonalTraining\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PersonalTrainer extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='personal_trainers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['specializations'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('personal_trainers.tenant_id',tenant()->id));}}
