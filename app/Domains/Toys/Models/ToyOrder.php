<?php declare(strict_types=1);

namespace App\Domains\Toys\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class ToyOrder extends Model { use HasUuids, SoftDeletes, TenantScoped;
    protected $table = 'toy_orders'; protected $fillable = ['uuid','tenant_id','seller_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','items_json','tags'];
    protected $casts = ['total_kopecks'=>'integer','payout_kopecks'=>'integer','items_json'=>'json','tags'=>'json'];
    protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('toy_orders.tenant_id',tenant()->id));}
}
