<?php declare(strict_types=1);
namespace

/**
 * CopywritingProject
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CopywritingProject();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Copywriting\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
 App\Domains\Copywriting\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class CopywritingProject extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='copywriting_projects';protected $fillable=['uuid','tenant_id','writer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','copy_type','word_count','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','word_count'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('copywriting_projects.tenant_id',tenant()->id));}}
