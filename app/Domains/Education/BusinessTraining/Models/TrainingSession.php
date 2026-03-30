<?php declare(strict_types=1);

namespace App\Domains\Education\BusinessTraining\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrainingSession extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='training_sessions';protected $fillable=['uuid','tenant_id','provider_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','training_type','training_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','training_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('training_sessions.tenant_id',tenant()->id));}
}
