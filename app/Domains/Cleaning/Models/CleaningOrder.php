<?php declare(strict_types=1);
namespace App\Domains\Cleaning\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class CleaningOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='cleaning_orders';protected $fillable=['uuid','tenant_id','service_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','order_date','duration_hours','area_sqm','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','order_date'=>'datetime','duration_hours'=>'integer','area_sqm'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('cleaning_orders.tenant_id',tenant()->id));}}
