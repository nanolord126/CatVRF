<?php declare(strict_types=1);
namespace App\Domains\VehicleDealing\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Vehicle extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='vehicles';protected $fillable=['uuid','tenant_id','dealer_id','correlation_id','make','model','year','price_kopecks','mileage','status','rating','tags'];protected $casts=['price_kopecks'=>'integer','mileage'=>'integer','year'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('vehicles.tenant_id',tenant()->id));}}
