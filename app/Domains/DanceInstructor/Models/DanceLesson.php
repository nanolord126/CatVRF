<?php declare(strict_types=1);
namespace App\Domains\DanceInstructor\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DanceLesson extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='dance_lessons';protected $fillable=['uuid','tenant_id','teacher_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','dance_style','lesson_hours','lesson_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lesson_hours'=>'integer','lesson_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('dance_lessons.tenant_id',tenant()->id));}}
