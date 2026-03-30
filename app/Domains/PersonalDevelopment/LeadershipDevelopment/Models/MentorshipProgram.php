<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\LeadershipDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MentorshipProgram extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='mentorship_programs';protected $fillable=['uuid','tenant_id','mentor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','program_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('mentorship_programs.tenant_id',tenant()->id));}
}
