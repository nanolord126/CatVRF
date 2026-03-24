<?php declare(strict_types=1);
namespace App\Domains\InteriorDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class DesignProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='design_projects';protected $fillable=['uuid','tenant_id','designer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','style','space_sqm','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','space_sqm'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('design_projects.tenant_id',tenant()->id));}}
