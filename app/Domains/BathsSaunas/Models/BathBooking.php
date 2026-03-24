<?php declare(strict_types=1);
namespace App\Domains\BathsSaunas\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BathBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bath_bookings';protected $fillable=['uuid','tenant_id','bath_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','booking_date','duration_hours','bath_type','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','booking_date'=>'datetime','duration_hours'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bath_bookings.tenant_id',tenant()->id));}}
