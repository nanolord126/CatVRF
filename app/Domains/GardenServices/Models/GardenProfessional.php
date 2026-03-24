<?php declare(strict_types=1);
namespace App\Domains\GardenServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GardenProfessional extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='garden_professionals';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','services','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['services'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('garden_professionals.tenant_id',tenant()->id));}}
