<?php declare(strict_types=1);

namespace App\Domains\Insurance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsurancePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='insurance_policies';protected $fillable=['uuid','tenant_id','company_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','policy_type','coverage_amount','duration_months','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','coverage_amount'=>'integer','duration_months'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('insurance_policies.tenant_id',tenant()->id));}
}
