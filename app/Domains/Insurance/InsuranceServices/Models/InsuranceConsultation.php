<?php declare(strict_types=1);

namespace App\Domains\Insurance\InsuranceServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsuranceConsultation extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='insurance_consultations';protected $fillable=['uuid','tenant_id','agent_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','consultation_type','consultation_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','consultation_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('insurance_consultations.tenant_id',tenant()->id));}
}
