<?php declare(strict_types=1);
namespace App\Domains\PartySupplies\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PartyVendor extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='party_vendors';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','categories','rating','is_verified','tags'];protected $casts=['categories'=>'json','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('party_vendors.tenant_id',tenant()->id));}}
