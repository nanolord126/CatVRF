<?php declare(strict_types=1);
namespace A

/**
 * Bid
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Bid();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Collectibles\AuctionHouses\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pp\Domains\AuctionHouses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class Bid extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='bids';protected $fillable=['uuid','tenant_id','auction_id','bidder_id','correlation_id','bid_amount','payment_status','tags'];protected $casts=['bid_amount'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bids.tenant_id',tenant()->id));}}
