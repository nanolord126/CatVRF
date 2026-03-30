<?php declare(strict_types=1);

namespace App\Domains\Medical\NursingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NursingEngagement extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='nursing_engagements';protected $fillable=['uuid','tenant_id','agency_id','patient_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','care_type','hours_required','start_date','end_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_required'=>'integer','start_date'=>'datetime','end_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('nursing_engagements.tenant_id',tenant()->id));}
}
