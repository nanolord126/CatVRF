<?php declare(strict_types=1);
namespace App\Domains\DigitalMarketing\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class MarketingCampaign extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='marketing_campaigns';protected $fillable=['uuid','tenant_id','consultant_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','campaign_type','project_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','project_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('marketing_campaigns.tenant_id',tenant()->id));}}
