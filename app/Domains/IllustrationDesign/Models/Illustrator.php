<?php declare(strict_types=1);
namespace App\Domains\IllustrationDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Illustrator extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='illustrators';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','styles','price_kopecks_per_illustration','rating','is_verified','tags'];protected $casts=['styles'=>'json','price_kopecks_per_illustration'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('illustrators.tenant_id',tenant()->id));}}
