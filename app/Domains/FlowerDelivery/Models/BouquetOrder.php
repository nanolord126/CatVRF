<?php declare(strict_types=1);
namespace App\Domains\FlowerDelivery\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BouquetOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bouquet_orders';protected $fillable=['uuid','tenant_id','shop_id','customer_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','bouquet_type','recipient_address','delivery_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','delivery_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bouquet_orders.tenant_id',tenant()->id));}}
