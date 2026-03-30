<?php declare(strict_types=1);

namespace App\Domains\Fashion\PersonalStyling\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StylingSession extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='styling_sessions';protected $fillable=['uuid','tenant_id','stylist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','style_type','session_hours','session_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','session_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('styling_sessions.tenant_id',tenant()->id));}
}
