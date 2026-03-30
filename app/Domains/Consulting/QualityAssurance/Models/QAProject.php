<?php declare(strict_types=1);

namespace App\Domains\Consulting\QualityAssurance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class QAProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='qa_projects';protected $fillable=['uuid','tenant_id','tester_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','testing_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','testing_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('qa_projects.tenant_id',tenant()->id));}
}
