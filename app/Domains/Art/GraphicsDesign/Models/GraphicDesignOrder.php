<?php declare(strict_types=1);
namespace Ap

/**
 * GraphicDesignOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new GraphicDesignOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Art\GraphicsDesign\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
p\Domains\GraphicsDesign\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GraphicDesignOrder extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='graphic_design_orders';protected $fillable=['uuid','tenant_id','designer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','design_type','design_count','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','design_count'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('graphic_design_orders.tenant_id',tenant()->id));}}
