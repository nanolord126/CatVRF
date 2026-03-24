<?php declare(strict_types=1);
namespace App\Domains\GraphicsDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GraphicDesigner extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='graphic_designers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','price_kopecks_per_design','rating','is_verified','tags'];protected $casts=['specializations'=>'json','price_kopecks_per_design'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('graphic_designers.tenant_id',tenant()->id));}}
