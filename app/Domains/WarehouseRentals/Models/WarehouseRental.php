<?php declare(strict_types=1);
namespace App\Domains\WarehouseRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WarehouseRental extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='warehouse_rentals';protected $fillable=['uuid','tenant_id','warehouse_id','tenant_business_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','lease_start','lease_end','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lease_start'=>'datetime','lease_end'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('warehouse_rentals.tenant_id',tenant()->id));}}
