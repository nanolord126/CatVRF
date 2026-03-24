<?php declare(strict_types=1);
namespace App\Domains\TranslationServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Translator extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='translators';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','language_pairs','price_kopecks_per_word','rating','is_verified','tags'];protected $casts=['language_pairs'=>'json','price_kopecks_per_word'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('translators.tenant_id',tenant()->id));}}
