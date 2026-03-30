<?php declare(strict_types=1);

namespace App\Domains\Auto\CarWashing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WashingOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='washing_orders';protected $fillable=['uuid','tenant_id','station_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','service_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('washing_orders.tenant_id',tenant()->id));}
}
