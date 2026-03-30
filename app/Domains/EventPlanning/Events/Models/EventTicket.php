<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventTicket extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='event_tickets';protected $fillable=['uuid','tenant_id','event_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','ticket_code','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_tickets.tenant_id',tenant()->id));}
}
