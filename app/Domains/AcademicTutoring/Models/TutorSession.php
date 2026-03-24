<?php declare(strict_types=1);
namespace App\Domains\AcademicTutoring\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TutorSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='tutor_sessions';protected $fillable=['uuid','tenant_id','tutor_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','subject','session_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tutor_sessions.tenant_id',tenant()->id));}}
