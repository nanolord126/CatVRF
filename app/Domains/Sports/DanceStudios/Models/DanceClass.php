<?php declare(strict_types=1);

namespace App\Domains\Sports\DanceStudios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DanceClass extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='dance_classes';protected $fillable=['uuid','tenant_id','studio_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','class_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','class_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('dance_classes.tenant_id',tenant()->id));}
}
