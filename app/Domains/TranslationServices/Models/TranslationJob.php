<?php declare(strict_types=1);
namespace App\Domains\TranslationServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class TranslationJob extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='translation_jobs';protected $fillable=['uuid','tenant_id','translator_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','language_pair','word_count','delivery_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','word_count'=>'integer','delivery_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('translation_jobs.tenant_id',tenant()->id));}}
