<?php declare(strict_types=1);
namespace App\Domains\Copywriting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Copywriter extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='copywriters';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','price_kopecks_per_word','rating','is_verified','tags'];protected $casts=['specializations'=>'json','price_kopecks_per_word'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('copywriters.tenant_id',tenant()->id));}}
