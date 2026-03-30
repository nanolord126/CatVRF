<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicalInstruments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InstrumentLesson extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='instrument_lessons';protected $fillable=['uuid','tenant_id','teacher_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','instrument','lesson_hours','lesson_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lesson_hours'=>'integer','lesson_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('instrument_lessons.tenant_id',tenant()->id));}
}
