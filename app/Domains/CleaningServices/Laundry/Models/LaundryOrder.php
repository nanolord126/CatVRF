<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Laundry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LaundryOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='laundry_orders';protected $fillable=['uuid','tenant_id','shop_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','weight_kg','pickup_date','delivery_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','weight_kg'=>'float','pickup_date'=>'datetime','delivery_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('laundry_orders.tenant_id',tenant()->id));}
}
