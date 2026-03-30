<?php declare(strict_types=1);

namespace App\Domains\Consulting\SEOServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SEOContract extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='seo_contracts';protected $fillable=['uuid','tenant_id','specialist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','contract_type','months_duration','start_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','months_duration'=>'integer','start_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('seo_contracts.tenant_id',tenant()->id));}
}
