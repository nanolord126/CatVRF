<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SupplierTier — Классификация поставщиков по уровню в цепочке поставок
 * 
 * Уровни:
 * - TIER_1 (Level 1): Производитель (завод, фермер) - обязательно требует первичный документ
 * - TIER_2 (Level 2): Торговый дом
 * - TIER_3 (Level 3): Оптовик
 * - TIER_4 (Level 4): Розничный продавец/магазин
 */
final class SupplierTier extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_tiers';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'code',
        'level',
        'description',
        'allowed_transitions',
        'requires_primary_document',
        'is_manufacturer',
        'can_sell_direct',
        'max_chain_depth',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'allowed_transitions' => 'array',
        'requires_primary_document' => 'boolean',
        'is_manufacturer' => 'boolean',
        'can_sell_direct' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Tier codes
    public const CODE_MANUFACTURER = 'TIER_1';
    public const CODE_TRADING_HOUSE = 'TIER_2';
    public const CODE_WHOLESALER = 'TIER_3';
    public const CODE_RETAILER = 'TIER_4';

    /**
     * Отношения
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'supplier_tier_id');
    }

    public function supplyChainLinks(): HasMany
    {
        return $this->hasMany(SupplyChainLink::class, 'supplier_tier_id');
    }

    /**
     * Получить производителя
     */
    public static function getManufacturer(): ?self
    {
        return self::where('code', self::CODE_MANUFACTURER)->where('is_active', true)->first();
    }

    /**
     * Получить оптовика
     */
    public static function getWholesaler(): ?self
    {
        return self::where('code', self::CODE_WHOLESALER)->where('is_active', true)->first();
    }

    /**
     * Получить торговый дом
     */
    public static function getTradingHouse(): ?self
    {
        return self::where('code', self::CODE_TRADING_HOUSE)->where('is_active', true)->first();
    }

    /**
     * Получить розничного продавца
     */
    public static function getRetailer(): ?self
    {
        return self::where('code', self::CODE_RETAILER)->where('is_active', true)->first();
    }

    /**
     * Проверить может ли этот уровень продавать указанному уровню
     */
    public function canSellTo(SupplierTier $buyerTier): bool
    {
        if (!$this->allowed_transitions) {
            return true;
        }

        return in_array($buyerTier->code, $this->allowed_transitions);
    }

    /**
     * Получить максимальную длину цепочки от этого уровня
     */
    public function getMaxChainLength(): int
    {
        return $this->max_chain_depth ?? 4;
    }

    /**
     * Проверить является ли уровень производителем
     */
    public function isManufacturerLevel(): bool
    {
        return $this->is_manufacturer || $this->code === self::CODE_MANUFACTURER;
    }

    /**
     * Активировать уровень
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Деактивировать уровень
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }
}
