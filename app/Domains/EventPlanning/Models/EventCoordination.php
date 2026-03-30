<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventCoordination extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='event_coordinations';protected $fillable=['uuid','tenant_id','coordinator_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','event_type','coordination_hours','event_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','coordination_hours'=>'integer','event_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_coordinations.tenant_id',tenant()->id));}
}
