<?php declare(strict_types=1);

namespace App\Domains\Hotels\HotelManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Hotel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='hotels';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','address','room_types','price_kopecks_per_night','stars','is_verified','tags'];protected $casts=['room_types'=>'json','price_kopecks_per_night'=>'integer','stars'=>'integer','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('hotels.tenant_id',tenant()->id));}
}
