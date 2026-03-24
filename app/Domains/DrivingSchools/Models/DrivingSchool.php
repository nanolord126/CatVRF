<?php declare(strict_types=1);
namespace App\Domains\DrivingSchools\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DrivingSchool extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='driving_schools';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','categories','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['categories'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('driving_schools.tenant_id',tenant()->id));}}
