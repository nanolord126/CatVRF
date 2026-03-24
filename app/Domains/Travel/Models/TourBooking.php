<?php declare(strict_types=1);
namespace App\Domains\Travel\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TourBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='tour_bookings';protected $fillable=['uuid','tenant_id','tour_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','travelers_count','start_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','travelers_count'=>'integer','start_date'=>'date','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('tour_bookings.tenant_id',tenant()->id));}}
