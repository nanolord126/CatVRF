<?php declare(strict_types=1);

namespace App\Domains\RealEstate\ShopRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Storefront extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='storefronts';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','area_sqm','location','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['area_sqm'=>'integer','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('storefronts.tenant_id',tenant()->id));}
}
