<?php declare(strict_types=1);
namespace App\Domains\KidsCenters\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class KidsCenter extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='kids_centers';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','age_group','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('kids_centers.tenant_id',tenant()->id));}}
