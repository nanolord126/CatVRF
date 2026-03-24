<?php declare(strict_types=1);
namespace App\Domains\Billiards\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BilliardHall extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='billiard_halls';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_hour','table_count','rating','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','table_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('billiard_halls.tenant_id',tenant()->id));}}
