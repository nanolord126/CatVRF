<?php declare(strict_types=1);

namespace App\Domains\Education\KidsCenters\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='kids_bookings';protected $fillable=['uuid','tenant_id','center_id','parent_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','kids_count','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','kids_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('kids_bookings.tenant_id',tenant()->id));}
}
