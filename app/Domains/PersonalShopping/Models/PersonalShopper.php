<?php declare(strict_types=1);
namespace App\Domains\PersonalShopping\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class PersonalShopper extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='personal_shoppers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specialization','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('personal_shoppers.tenant_id',tenant()->id));}}
