<?php declare(strict_types=1);
namespace App\Domains\BeautyServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BeautyStudio extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='beauty_studios';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','address','services','price_kopecks_per_minute','rating','is_verified','tags'];protected $casts=['services'=>'json','price_kopecks_per_minute'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('beauty_studios.tenant_id',tenant()->id));}}
