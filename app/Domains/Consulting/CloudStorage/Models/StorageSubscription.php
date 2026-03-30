<?php declare(strict_types=1);

namespace App\Domains\Consulting\CloudStorage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StorageSubscription extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='storage_subscriptions';protected $fillable=['uuid','tenant_id','provider_id','user_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','plan_type','start_date','end_date','storage_gb','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','start_date'=>'datetime','end_date'=>'datetime','storage_gb'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('storage_subscriptions.tenant_id',tenant()->id));}
}
