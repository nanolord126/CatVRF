<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Laundry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LaundryShop extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='laundry_shops';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','price_kopecks_per_kg','delivery_fee','rating','is_verified','tags'];protected $casts=['price_kopecks_per_kg'=>'integer','delivery_fee'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('laundry_shops.tenant_id',tenant()->id));}
}
