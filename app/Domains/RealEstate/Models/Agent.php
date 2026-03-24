<?php declare(strict_types=1);
namespace App\Domains\RealEstate\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Agent extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='real_estate_agents';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','license','price_commission_percent','rating','is_verified','tags'];protected $casts=['price_commission_percent'=>'float','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('real_estate_agents.tenant_id',tenant()->id));}}
