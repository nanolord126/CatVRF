<?php declare(strict_types=1);

namespace App\Domains\Flowers\FlowerDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FloristShop extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='florist_shops';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','flowers_available','price_kopecks_per_bouquet','rating','is_verified','tags'];protected $casts=['flowers_available'=>'json','price_kopecks_per_bouquet'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('florist_shops.tenant_id',tenant()->id));}
}
