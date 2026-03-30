<?php declare(strict_types=1);

namespace App\Domains\Archived\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WeddingProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='wedding_projects';protected $fillable=['uuid','tenant_id','planner_id','couple_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','wedding_date','guest_count','venue_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','wedding_date'=>'datetime','guest_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('wedding_projects.tenant_id',tenant()->id));}
}
