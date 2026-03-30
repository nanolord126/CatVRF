<?php declare(strict_types=1);

namespace App\Domains\Hotels\Lodging\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LodgingBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='lodging_bookings';protected $fillable=['uuid','tenant_id','lodge_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','check_in','check_out','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','check_in'=>'date','check_out'=>'date','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('lodging_bookings.tenant_id',tenant()->id));}
}
