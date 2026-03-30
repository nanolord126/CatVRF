<?php declare(strict_types=1);

namespace App\Domains\Auto\VehicleDealing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VehicleSale extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='vehicle_sales';protected $fillable=['uuid','tenant_id','vehicle_id','buyer_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('vehicle_sales.tenant_id',tenant()->id));}
}
