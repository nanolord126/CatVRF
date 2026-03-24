<?php declare(strict_types=1);

namespace App\Domains\Toys\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Toy extends Model { use HasUuids, SoftDeletes, TenantScoped;
    protected $table = 'toys'; protected $fillable = ['uuid','tenant_id','seller_id','correlation_id','name','price_kopecks','age_from','age_to','stock','tags'];
    protected $casts = ['price_kopecks'=>'integer','age_from'=>'integer','age_to'=>'integer','stock'=>'integer','tags'=>'json'];
    protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('toys.tenant_id',tenant()->id));}
}
