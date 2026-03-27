<?php declare(strict_types=1);
namespac

/**
 * BoardGameCafe
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new BoardGameCafe();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\HobbyAndCraft\HobbyAndCraft\BoardGames\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
e App\Domains\HobbyAndCraft\HobbyAndCraft\BoardGames\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BoardGameCafe extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='board_game_cafes';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','table_count','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['table_count'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('board_game_cafes.tenant_id',tenant()->id));}}
