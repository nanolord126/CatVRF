<?php declare(strict_types=1);

namespace App\Domains\PartySupplies\Gifts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GiftOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;
        protected $table = 'gift_orders'; protected $fillable = ['uuid','tenant_id','seller_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','items_json','tags'];
        protected $casts = ['total_kopecks'=>'integer','payout_kopecks'=>'integer','items_json'=>'json','tags'=>'json'];
        protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('gift_orders.tenant_id',tenant()->id));}
}
