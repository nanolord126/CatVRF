<?php declare(strict_types=1);
namespace App\Domains\DrivingSchools\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DrivingLesson extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='driving_lessons';protected $fillable=['uuid','tenant_id','school_id','student_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','lesson_date','duration_hours','category','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lesson_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('driving_lessons.tenant_id',tenant()->id));}}
