<?php declare(strict_types=1);

namespace App\Domains\Archived\PartySupplies\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PartyOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='party_orders';protected $fillable=['uuid','tenant_id','vendor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','items','delivery_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','items'=>'json','delivery_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('party_orders.tenant_id',tenant()->id));}
}
