<?php declare(strict_types=1);
namespace App\Domains\CarRental\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class RentalBooking extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='rental_bookings';protected $fillable=['uuid','tenant_id','car_id','renter_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','pickup_date','return_date','days_count','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','pickup_date'=>'datetime','return_date'=>'datetime','days_count'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('rental_bookings.tenant_id',tenant()->id));}}
