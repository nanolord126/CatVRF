<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApartmentRental extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='apartment_rentals';protected $fillable=['uuid','tenant_id','apartment_id','guest_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','check_in','check_out','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','check_in'=>'datetime','check_out'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('apartment_rentals.tenant_id',tenant()->id));}
}
