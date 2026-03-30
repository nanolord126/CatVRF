<?php declare(strict_types=1);

namespace App\Domains\Hotels\HotelManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='hotel_bookings';protected $fillable=['uuid','tenant_id','hotel_id','guest_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','room_type','check_in','check_out','nights_count','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','check_in'=>'datetime','check_out'=>'datetime','nights_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('hotel_bookings.tenant_id',tenant()->id));}
}
