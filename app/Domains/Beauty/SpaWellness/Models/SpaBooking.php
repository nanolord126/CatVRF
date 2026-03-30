<?php declare(strict_types=1);

namespace App\Domains\Beauty\SpaWellness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SpaBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='spa_bookings';protected $fillable=['uuid','tenant_id','spa_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','treatment_type','duration_minutes','booking_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','duration_minutes'=>'integer','booking_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('spa_bookings.tenant_id',tenant()->id));}
}
