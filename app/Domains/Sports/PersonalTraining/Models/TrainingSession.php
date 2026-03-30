<?php declare(strict_types=1);

namespace App\Domains\Sports\PersonalTraining\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrainingSession extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='training_sessions';protected $fillable=['uuid','tenant_id','trainer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','workout_type','session_hours','session_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','session_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('training_sessions.tenant_id',tenant()->id));}
}
