<?php declare(strict_types=1);
namespace App\Domains\VideoEditing\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class VideoProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='video_projects';protected $fillable=['uuid','tenant_id','editor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','editing_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','editing_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('video_projects.tenant_id',tenant()->id));}}
