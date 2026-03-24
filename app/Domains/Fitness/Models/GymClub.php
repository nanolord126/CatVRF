<?php declare(strict_types=1);
namespace App\Domains\Fitness\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GymClub extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='gym_clubs';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','classes_available','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['classes_available'=>'json','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('gym_clubs.tenant_id',tenant()->id));}}
