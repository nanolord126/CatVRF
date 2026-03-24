<?php declare(strict_types=1);
namespace App\Domains\EventHalls\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class EventHall extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='event_halls';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','capacity','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['capacity'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('event_halls.tenant_id',tenant()->id));}}
