<?php declare(strict_types=1);

namespace App\Domains\Legal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LegalConsultation extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='legal_consultations';protected $fillable=['uuid','tenant_id','lawyer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','consultation_date','duration_hours','case_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','consultation_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('legal_consultations.tenant_id',tenant()->id));}
}
