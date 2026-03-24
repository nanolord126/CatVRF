<?php declare(strict_types=1);
namespace App\Domains\MassageTherapy\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MassageTherapist extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='massage_therapists';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','massage_types','price_kopecks_per_minute','rating','is_verified','tags'];protected $casts=['massage_types'=>'json','price_kopecks_per_minute'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('massage_therapists.tenant_id',tenant()->id));}}
