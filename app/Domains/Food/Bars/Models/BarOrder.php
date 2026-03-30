<?php declare(strict_types=1);

namespace App\Domains\Food\Bars\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BarOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='bar_orders';protected $fillable=['uuid','tenant_id','bar_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','order_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','order_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bar_orders.tenant_id',tenant()->id));}
}
