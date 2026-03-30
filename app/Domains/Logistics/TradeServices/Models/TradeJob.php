<?php declare(strict_types=1);

namespace App\Domains\Logistics\TradeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TradeJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='trade_jobs';protected $fillable=['uuid','tenant_id','tradesperson_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','job_date','duration_hours','job_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','job_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('trade_jobs.tenant_id',tenant()->id));}
}
