<?php declare(strict_types=1);
namespace App\Domains\Accounting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class AccountingService extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='accounting_services';protected $fillable=['uuid','tenant_id','accountant_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','service_type','request_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','request_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('accounting_services.tenant_id',tenant()->id));}}
