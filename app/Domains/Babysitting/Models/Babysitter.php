<?php declare(strict_types=1);
namespace App\Domains\Babysitting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Babysitter extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='babysitters';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','experience_years','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['experience_years'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('babysitters.tenant_id',tenant()->id));}}
