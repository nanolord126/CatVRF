<?php declare(strict_types=1);
namespace App\Domains\ContentProduction\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class ContentProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='content_projects';protected $fillable=['uuid','tenant_id','creator_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','content_type','production_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','production_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('content_projects.tenant_id',tenant()->id));}}
