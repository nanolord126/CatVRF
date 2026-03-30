<?php declare(strict_types=1);

namespace App\Domains\Education\AcademicTutoring\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TutorSession extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='tutor_sessions';protected $fillable=['uuid','tenant_id','tutor_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','subject','session_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tutor_sessions.tenant_id',tenant()->id));}
}
