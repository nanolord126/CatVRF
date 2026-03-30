<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Karaoke\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KaraokeBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='karaoke_bookings';protected $fillable=['uuid','tenant_id','club_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','room_number','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','room_number'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('karaoke_bookings.tenant_id',tenant()->id));}
}
