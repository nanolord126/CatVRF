<?php declare(strict_types=1);
namespace App\Domains\AuctionHouses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Bid extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bids';protected $fillable=['uuid','tenant_id','auction_id','bidder_id','correlation_id','bid_amount','payment_status','tags'];protected $casts=['bid_amount'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bids.tenant_id',tenant()->id));}}
