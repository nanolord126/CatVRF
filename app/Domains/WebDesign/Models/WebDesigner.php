<?php declare(strict_types=1);
namespace App\Domains\WebDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WebDesigner extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='web_designers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','base_price_kopecks','rating','is_verified','tags'];protected $casts=['specializations'=>'json','base_price_kopecks'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('web_designers.tenant_id',tenant()->id));}}
