<?php declare(strict_types=1);
namespace App\Domains\SpaWellness\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class SpaBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='spa_bookings';protected $fillable=['uuid','tenant_id','spa_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','treatment_type','duration_minutes','booking_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','duration_minutes'=>'integer','booking_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('spa_bookings.tenant_id',tenant()->id));}}
