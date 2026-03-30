<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Babysitting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BabysittingSession extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='babysitting_sessions';protected $fillable=['uuid','tenant_id','sitter_id','parent_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','session_date','duration_hours','kids_ages','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('babysitting_sessions.tenant_id',tenant()->id));}
}
