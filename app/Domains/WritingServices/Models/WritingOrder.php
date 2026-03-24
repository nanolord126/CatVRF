<?php declare(strict_types=1);
namespace App\Domains\WritingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class WritingOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='writing_orders';protected $fillable=['uuid','tenant_id','writer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','word_count','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','word_count'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('writing_orders.tenant_id',tenant()->id));}}
