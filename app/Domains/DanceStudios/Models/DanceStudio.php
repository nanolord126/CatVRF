<?php declare(strict_types=1);
namespace App\Domains\DanceStudios\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DanceStudio extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='dance_studios';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','style','price_kopecks_per_class','rating','is_verified','tags'];protected $casts=['price_kopecks_per_class'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('dance_studios.tenant_id',tenant()->id));}}
