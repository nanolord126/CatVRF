<?php declare(strict_types=1);

namespace App\Domains\Flowers\GiftDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GiftVendor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='gift_vendors';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','categories','rating','is_verified','tags'];protected $casts=['categories'=>'json','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('gift_vendors.tenant_id',tenant()->id));}
}
