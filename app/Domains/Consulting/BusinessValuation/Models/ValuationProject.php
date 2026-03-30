<?php declare(strict_types=1);

namespace App\Domains\Consulting\BusinessValuation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ValuationProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='valuation_projects';protected $fillable=['uuid','tenant_id','expert_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','valuation_type','valuation_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','valuation_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('valuation_projects.tenant_id',tenant()->id));}
}
