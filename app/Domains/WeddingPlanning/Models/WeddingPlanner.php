<?php declare(strict_types=1);
namespace App\Domains\WeddingPlanning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WeddingPlanner extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='wedding_planners';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','services','base_price_kopecks','price_per_guest','rating','is_verified','tags'];protected $casts=['services'=>'json','base_price_kopecks'=>'integer','price_per_guest'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('wedding_planners.tenant_id',tenant()->id));}}
